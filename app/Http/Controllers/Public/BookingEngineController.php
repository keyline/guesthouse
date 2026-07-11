<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\InventoryService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingEngineController extends Controller
{
    private const MAX_NIGHTS = 30;

    public function search(Request $request, AvailabilityService $availability): View
    {
        $properties = Property::query()
            ->where('status', Property::STATUS_ACTIVE)
            ->whereIn('property_type', [Property::TYPE_GUEST_HOUSE, Property::TYPE_MIXED])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $checkIn = $this->parseDate($request->string('check_in')->toString()) ?? CarbonImmutable::today();
        $checkOut = $this->parseDate($request->string('check_out')->toString()) ?? $checkIn->addDay();

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            $checkOut = $checkIn->addDay();
        }

        $property = $request->integer('property_id')
            ? $properties->firstWhere('id', $request->integer('property_id'))
            : null;

        $results = collect();

        if ($property && $checkIn->greaterThanOrEqualTo(CarbonImmutable::today())) {
            $nights = $checkIn->diffInDays($checkOut);

            $results = RatePlan::query()
                ->where('property_id', $property->id)
                ->where('status', RatePlan::STATUS_ACTIVE)
                ->with(['roomType' => fn ($query) => $query->with(['images', 'amenities'])])
                ->orderBy('room_type_id')
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (RatePlan $plan) => $plan->roomType?->status === RoomType::STATUS_ACTIVE)
                ->groupBy('room_type_id')
                ->map(function ($plans) use ($property, $availability, $checkIn, $checkOut) {
                    $roomType = $plans->first()->roomType;

                    $sellable = $availability->onlineTypeAvailability(
                        $property->id,
                        $roomType->id,
                        $checkIn,
                        $checkOut,
                    );

                    return [
                        'roomType' => $roomType,
                        'sellable' => $sellable,
                        'plans' => $plans->map(fn (RatePlan $plan) => [
                            'plan' => $plan,
                            'totalMinor' => $availability->quote($plan, $checkIn, $checkOut),
                        ])->filter(fn (array $row) => $row['totalMinor'] !== null && $row['totalMinor'] > 0)->values(),
                    ];
                })
                ->filter(fn (array $row) => $row['sellable'] > 0 && $row['plans']->isNotEmpty())
                ->values();
        }

        return view('public.booking.search', [
            'properties' => $properties,
            'property' => $property,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'nights' => $checkIn->diffInDays($checkOut),
            'results' => $results,
            'searched' => (bool) $property,
        ]);
    }

    public function store(Request $request, AvailabilityService $availability, InventoryService $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'rate_plan_id' => ['required', 'integer', Rule::exists(RatePlan::class, 'id')->where('status', RatePlan::STATUS_ACTIVE)],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'adults' => ['required', 'integer', 'min:1', 'max:10'],
            'children' => ['required', 'integer', 'min:0', 'max:10'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ]);

        $plan = RatePlan::query()->with(['property', 'roomType'])->findOrFail($validated['rate_plan_id']);
        $checkIn = CarbonImmutable::parse($validated['check_in']);
        $checkOut = CarbonImmutable::parse($validated['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        if ($plan->property->status !== Property::STATUS_ACTIVE) {
            return back()->withErrors(['rate_plan_id' => 'This property is not accepting online bookings.'])->withInput();
        }

        if ($nights > self::MAX_NIGHTS) {
            return back()->withErrors(['check_out' => 'Online bookings are limited to '.self::MAX_NIGHTS.' nights. Please contact the property directly.'])->withInput();
        }

        $sellable = $availability->onlineTypeAvailability($plan->property_id, $plan->room_type_id, $checkIn, $checkOut);

        if ($sellable < 1) {
            return back()->withErrors(['rate_plan_id' => 'Sorry, this room type just sold out for your dates.'])->withInput();
        }

        $totalMinor = $availability->quote($plan, $checkIn, $checkOut);

        if ($totalMinor === null || $totalMinor < 1) {
            return back()->withErrors(['rate_plan_id' => 'This rate is not bookable online for your dates.'])->withInput();
        }

        $user = $request->user();

        $booking = DB::transaction(function () use ($validated, $plan, $checkIn, $checkOut, $nights, $totalMinor, $user, $inventory): Booking {
            $booking = Booking::query()->create([
                'property_id' => $plan->property_id,
                'room_type_id' => $plan->room_type_id,
                'room_id' => null,
                'rate_plan_id' => $plan->id,
                'user_id' => $user?->hasRole(User::ROLE_CUSTOMER) ? $user->id : null,
                'status' => Booking::STATUS_PENDING,
                'source' => Booking::SOURCE_ONLINE,
                'guest_name' => $validated['guest_name'],
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_phone' => $validated['guest_phone'],
                'check_in_date' => $checkIn->toDateString(),
                'check_out_date' => $checkOut->toDateString(),
                'nights' => $nights,
                'adults' => $validated['adults'],
                'children' => $validated['children'],
                'total_amount_minor' => $totalMinor,
                'currency' => $plan->currency,
                'special_requests' => $validated['special_requests'] ?? null,
            ]);

            $inventory->syncBooking($booking);

            return $booking;
        });

        return redirect()->route('book.confirmation', ['bookingNumber' => $booking->booking_number]);
    }

    public function confirmation(string $bookingNumber): View
    {
        $booking = Booking::query()
            ->where('booking_number', $bookingNumber)
            ->where('source', Booking::SOURCE_ONLINE)
            ->with(['property', 'roomType', 'ratePlan'])
            ->firstOrFail();

        return view('public.booking.confirmation', ['booking' => $booking]);
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
    }
}
