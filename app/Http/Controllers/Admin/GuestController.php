<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuestRequest;
use App\Models\Booking;
use App\Models\Corporate;
use App\Models\User;
use App\Support\AdminNavigation;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    public function index(Request $request): View
    {
        $guests = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->with('corporate')
            ->withCount('bookings')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('corporate', fn ($corporate) => $corporate
                            ->where('legal_name', 'like', '%'.$search.'%')
                            ->orWhere('gstin', 'like', '%'.strtoupper($search).'%'));
                });
            })
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.guests.index', [
            'guests' => $guests,
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function create(): View
    {
        return view('admin.guests.create', [
            'guest' => new User([
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
            ]),
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => ['required', Rule::in(array_keys(PhoneNumber::countryCodes()))],
            'mobile' => ['required', 'string', 'max:20'],
        ]);
        $phone = PhoneNumber::normalize($validated['country_code'], $validated['mobile']);
        $guest = User::query()->with('corporate')->where('role', User::ROLE_CUSTOMER)->where('phone_e164', $phone['e164'])->first();

        return response()->json([
            'found' => (bool) $guest,
            'phone_e164' => $phone['e164'],
            'guest' => $guest ? [
                'id' => $guest->id, 'public_id' => $guest->public_id, 'name' => $guest->name,
                'email' => $guest->email, 'phone' => $guest->phone_e164, 'customer_type' => $guest->customer_type,
                'corporate' => $guest->corporate?->legal_name, 'bookings_count' => $guest->bookings()->count(),
                'last_booking' => $guest->last_booking_at?->format('d M Y'),
                'show_url' => route('admin.guests.show', $guest), 'edit_url' => route('admin.guests.edit', $guest),
            ] : null,
        ]);
    }

    public function store(GuestRequest $request): RedirectResponse
    {
        $guest = DB::transaction(function () use ($request): User {
            $corporate = $request->input('customer_type') === 'corporate'
                ? Corporate::query()->create($request->corporateAttributes())
                : null;
            return User::query()->create($request->attributesForModel() + ['corporate_id' => $corporate?->id]);
        });

        $this->linkHistoricalBookings($guest);

        return redirect()
            ->route('admin.guests.show', $guest)
            ->with('status', 'Guest created successfully.');
    }

    public function show(User $guest): View
    {
        $this->ensureCustomer($guest);

        $guest->load(['corporate', 'bookings.property', 'bookings.roomType', 'bookings.room']);

        return view('admin.guests.show', [
            'guest' => $guest,
            'stats' => $this->stats($guest),
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function edit(User $guest): View
    {
        $this->ensureCustomer($guest);

        $guest->load('corporate');
        return view('admin.guests.edit', [
            'guest' => $guest,
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function update(GuestRequest $request, User $guest): RedirectResponse
    {
        $this->ensureCustomer($guest);

        DB::transaction(function () use ($request, $guest): void {
            $corporate = null;
            if ($request->input('customer_type') === 'corporate') {
                $corporate = $guest->corporate;
                if ($corporate) {
                    $corporate->update($request->corporateAttributes());
                } else {
                    $corporate = Corporate::query()->create($request->corporateAttributes());
                }
            }
            $guest->update($request->attributesForModel() + ['corporate_id' => $corporate?->id]);
        });
        $this->linkHistoricalBookings($guest);

        return redirect()
            ->route('admin.guests.show', $guest)
            ->with('status', 'Guest updated successfully.');
    }

    public function destroy(User $guest): RedirectResponse
    {
        $this->ensureCustomer($guest);

        $guest->update(['is_active' => false]);

        return redirect()
            ->route('admin.guests.index')
            ->with('status', 'Guest deactivated successfully.');
    }

    private function ensureCustomer(User $guest): void
    {
        abort_unless($guest->role === User::ROLE_CUSTOMER, 404);
    }

    private function linkHistoricalBookings(User $guest): void
    {
        Booking::query()
            ->whereNull('user_id')
            ->where('guest_email', $guest->email)
            ->update(['user_id' => $guest->id]);

        $lastBooking = $guest->bookings()->max('check_in_date');

        if ($lastBooking) {
            $guest->forceFill(['last_booking_at' => $lastBooking])->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(User $guest): array
    {
        $bookings = $guest->bookings;

        return [
            'bookings' => $bookings->count(),
            'nights' => $bookings->sum('nights'),
            'spent' => $bookings->sum('total_amount_minor'),
            'lastBooking' => $bookings->max('check_in_date'),
        ];
    }
}
