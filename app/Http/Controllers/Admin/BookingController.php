<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingRequest;
use App\Models\Booking;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Corporate;
use App\Models\Discount;
use App\Models\PropertyRoomType;
use App\Services\Rooms\AmenityResolver;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\CancellationService;
use App\Services\Booking\InventoryService;
use App\Services\Booking\PricingService;
use App\Services\Payments\Gst;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $bookings = $scope->apply(Booking::query())
            ->with(['property', 'roomType', 'room', 'ratePlan', 'payments'])
            ->when($request->integer('property_id'), fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->date('from'), fn ($query, $from) => $query->where('check_out_date', '>', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->where('check_in_date', '<', $to))
            ->orderBy('check_in_date')
            ->orderBy('guest_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'properties' => $this->properties($scope),
            'statuses' => Booking::statusLabels(),
            'pendingRefundCount' => $scope->apply(Booking::query())
                ->whereIn('refund_state', [Booking::REFUND_FAILED, Booking::REFUND_MANUAL])
                ->count(),
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function create(Request $request, AdminPropertyScope $scope, AvailabilityService $availability): View
    {
        $selectedPropertyId = $request->integer('property_id') ?: $scope->selectedPropertyId();
        $selectedGuest = $request->integer('user_id')
            ? User::query()->where('role', User::ROLE_CUSTOMER)->find($request->integer('user_id'))
            : null;

        $checkIn = CarbonImmutable::parse(old('check_in_date', now()->toDateString()));
        $checkOut = CarbonImmutable::parse(old('check_out_date', now()->addDay()->toDateString()));

        return view('admin.bookings.create', [
            'booking' => new Booking([
                'property_id' => $selectedPropertyId,
                'room_id' => $request->integer('room_id') ?: null,
                'status' => Booking::STATUS_CONFIRMED,
                'source' => Booking::SOURCE_DIRECT,
                'check_in_date' => now()->toDateString(),
                'check_out_date' => now()->addDay()->toDateString(),
                'adults' => 1,
                'children' => 0,
                'currency' => 'INR',
                'user_id' => $selectedGuest?->id,
                'guest_name' => $selectedGuest?->name,
                'guest_email' => $selectedGuest?->email,
                'guest_phone' => $selectedGuest?->phone_e164 ?: $selectedGuest?->phone,
            ]),
            'properties' => $this->properties($scope),
            'roomBoard' => $this->roomBoardByFloor($scope, $selectedPropertyId, $checkIn, $checkOut, null, $availability),
            'guests' => $this->guests(),
            'statuses' => BookingRequest::statusOptionsFor(null),
            'sources' => $this->sources(),
            'corporates' => $this->corporates(),
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function roomBoardStatus(Request $request, AdminPropertyScope $scope, AvailabilityService $availability): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'booking_id' => ['nullable', 'integer'],
        ]);

        abort_unless($scope->canAccessProperty((int) $validated['property_id']), 404);

        $cards = $this->roomCards(
            (int) $validated['property_id'],
            CarbonImmutable::parse($validated['check_in']),
            CarbonImmutable::parse($validated['check_out']),
            isset($validated['booking_id']) ? (int) $validated['booking_id'] : null,
            $availability,
        );

        return response()->json(['rooms' => $cards->values()]);
    }

    public function store(BookingRequest $request, InventoryService $inventory, AvailabilityService $availability, PricingService $pricing, CancellationService $cancellation): RedirectResponse
    {
        $booking = DB::transaction(function () use ($request, $inventory, $availability, $pricing, $cancellation): Booking {
            $attributes = $request->attributesForModel();
            $attributes['user_id'] ??= $this->resolveGuestProfile($attributes)?->id;

            $roomIds = collect($request->validated('room_ids', []))->filter()->map(fn ($id) => (int) $id)->values();
            $this->lockAndRecheckRooms($roomIds, $attributes, $availability);

            $rooms = Room::query()->whereKey($roomIds->all())->get()->keyBy('id');
            $checkIn = CarbonImmutable::parse($attributes['check_in_date']);
            $checkOut = CarbonImmutable::parse($attributes['check_out_date']);
            $propertyRuleSet = Property::query()->findOrFail($attributes['property_id'])->publishedRulesFor($checkIn);
            $nights = max(1, $checkIn->diffInDays($checkOut));
            $plans = $rooms->pluck('room_type_id')->unique()
                ->mapWithKeys(fn (int $typeId) => [$typeId => RatePlan::defaultFor($attributes['property_id'], $typeId)]);

            $groupCode = $roomIds->count() > 1 ? (string) Str::uuid() : null;
            $lines = $this->priceRooms($request, $attributes, $roomIds, $rooms, $plans, $checkIn, $checkOut, $nights, $pricing);
            $created = collect();

            foreach ($roomIds as $index => $roomId) {
                $room = $rooms[$roomId];
                $booking = Booking::query()->create(array_merge($attributes, [
                    'room_id' => $room->id,
                    'room_type_id' => $room->room_type_id,
                    'rate_plan_id' => $plans[$room->room_type_id]?->id,
                    'cancellation_policy_snapshot' => $cancellation->snapshotFor($plans[$room->room_type_id], $checkIn),
                    'room_configuration_snapshot' => $this->roomConfigurationSnapshot($room),
                    'property_rules_snapshot' => $propertyRuleSet ? array_merge($propertyRuleSet->snapshot(), [
                        'recorded_at' => now()->toIso8601String(),
                        'recorded_by_user_id' => $request->user()?->id,
                        'channel' => 'admin',
                    ]) : null,
                    'property_rules_version' => $propertyRuleSet?->version,
                    'booking_group_code' => $groupCode,
                ], $lines[$index]));
                $booking->guests()->create([
                    'user_id' => $booking->user_id, 'role' => 'primary', 'guest_type' => 'adult',
                    'full_name' => $booking->guest_name, 'phone' => $booking->guest_phone,
                    'email' => $booking->guest_email, 'nationality' => 'Indian', 'is_staying' => true,
                ]);
                $inventory->syncBooking($booking);
                $created->push($booking);
            }

            // A group counts as one use per automatic offer.
            $created->pluck('discount_id')->filter()->unique()
                ->each(fn (int $discountId) => Discount::query()->whereKey($discountId)->increment('times_used'));

            $this->touchGuestLastBooking($created->first());

            return $created->first();
        });

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('status', $booking->booking_group_code ? 'Multi-room booking created successfully.' : 'Booking created successfully.');
    }

    private function roomConfigurationSnapshot(Room $room): ?array
    {
        $configuration = PropertyRoomType::query()->where('property_id', $room->property_id)->where('room_type_id', $room->room_type_id)->first();
        if (! $configuration) return null;
        return ['room_type'=>$configuration->displayName(),
            'amenities'=>app(AmenityResolver::class)->forRoom($room)->where('is_guest_visible', true)->pluck('name')->values()->all(),
            'capacity'=>['adults'=>$configuration->max_adults,'children'=>$configuration->max_children],
            'pet_friendly'=>$configuration->is_pet_friendly,
            'extra_bed'=>['available'=>$configuration->extra_bed_available,'maximum'=>$configuration->max_extra_beds,'charge_minor'=>$configuration->extra_bed_charge_minor,'basis'=>$configuration->extra_bed_charge_basis]];
    }

    public function show(Booking $booking, AdminPropertyScope $scope, AvailabilityService $availability, CancellationService $cancellation): View
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);
        $booking->load(['property', 'roomType', 'room', 'ratePlan', 'guests.documents', 'stay', 'corporate']);

        $assignableRooms = $booking->room_id
            ? collect()
            : $availability->availableRooms(
                $booking->property_id,
                $booking->room_type_id,
                $booking->check_in_date,
                $booking->check_out_date,
                $booking->id,
            );

        $groupBookings = $booking->booking_group_code
            ? Booking::query()->with('room')->where('booking_group_code', $booking->booking_group_code)->orderBy('id')->get()
            : collect([$booking]);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'assignableRooms' => $assignableRooms,
            'groupBookings' => $groupBookings,
            'cancelQuote' => $booking->status === Booking::STATUS_CANCELLED
                ? null
                : $cancellation->quote($groupBookings->first(), $groupBookings),
            'cancellationReasons' => self::cancellationReasons(),
            'payments' => \App\Models\Payment::query()
                ->where(fn ($query) => $query
                    ->where('booking_id', $booking->id)
                    ->when($booking->booking_group_code, fn ($query) => $query->orWhere('booking_group_code', $booking->booking_group_code)))
                ->orderBy('id')
                ->get(),
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function assignRoom(Request $request, Booking $booking, AdminPropertyScope $scope, AvailabilityService $availability): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);

        $validated = $request->validate([
            'room_id' => ['required', 'integer'],
        ]);

        $room = Room::query()
            ->where('id', $validated['room_id'])
            ->where('property_id', $booking->property_id)
            ->where('room_type_id', $booking->room_type_id)
            ->first();

        if (! $room || ! $availability->roomIsAvailable($room, $booking->check_in_date, $booking->check_out_date, $booking->id)) {
            return back()->withErrors(['room_id' => 'That room is not available for this stay.']);
        }

        $booking->update(['room_id' => $room->id]);

        return back()->with('status', "Room {$room->room_number} assigned to {$booking->booking_number}.");
    }

    public function edit(Booking $booking, AdminPropertyScope $scope, AvailabilityService $availability): View
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);

        $checkIn = CarbonImmutable::parse(old('check_in_date', $booking->check_in_date->toDateString()));
        $checkOut = CarbonImmutable::parse(old('check_out_date', $booking->check_out_date->toDateString()));

        return view('admin.bookings.edit', [
            'booking' => $booking,
            'properties' => $this->properties($scope),
            'roomBoard' => $this->roomBoardByFloor($scope, $booking->property_id, $checkIn, $checkOut, $booking->id, $availability),
            'guests' => $this->guests(),
            'statuses' => BookingRequest::statusOptionsFor($booking),
            'sources' => $this->sources(),
            'corporates' => $this->corporates(),
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function update(BookingRequest $request, Booking $booking, AdminPropertyScope $scope, InventoryService $inventory, CancellationService $cancellation): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);

        $previous = [
            'property_id' => $booking->property_id,
            'room_type_id' => $booking->room_type_id,
            'check_in_date' => $booking->check_in_date->toDateString(),
            'check_out_date' => $booking->check_out_date->toDateString(),
        ];

        DB::transaction(function () use ($request, $booking, $inventory, $previous, $cancellation): void {
            $attributes = $request->attributesForModel();
            $attributes['user_id'] ??= $this->resolveGuestProfile($attributes)?->id;

            // A moved check-in moves the policy deadlines with it: re-freeze
            // the same plan's policy against the new date.
            if (($attributes['check_in_date'] ?? null) && $attributes['check_in_date'] !== $previous['check_in_date']) {
                $attributes['cancellation_policy_snapshot'] = $cancellation->snapshotFor(
                    $booking->ratePlan,
                    CarbonImmutable::parse($attributes['check_in_date']),
                );
            }
            $booking->update($attributes);
            $booking->primaryGuest()->updateOrCreate([], [
                'user_id' => $booking->user_id,
                'guest_type' => 'adult',
                'full_name' => $booking->guest_name,
                'phone' => $booking->guest_phone,
                'email' => $booking->guest_email,
                'nationality' => $booking->primaryGuest?->nationality ?: 'Indian',
                'is_staying' => true,
            ]);
            $inventory->syncBooking($booking, $previous);
            $this->touchGuestLastBooking($booking);
        });

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('status', 'Booking updated successfully.');
    }

    public function destroy(Request $request, Booking $booking, AdminPropertyScope $scope, InventoryService $inventory, CancellationService $cancellation): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);

        if ($booking->status === Booking::STATUS_CANCELLED) {
            return redirect()
                ->route('admin.bookings.index')
                ->with('status', 'Booking is already cancelled.');
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', \Illuminate\Validation\Rule::in(array_keys(self::cancellationReasons()))],
            'reason_note' => ['nullable', 'string', 'max:255'],
            'refund_override' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        // Only a super admin may deviate from the policy amount, and the
        // override lands in the activity log with the rest of the change.
        $override = null;
        if ($request->filled('refund_override')) {
            abort_unless($request->user()->hasRole(User::ROLE_SUPER_ADMIN), 403);
            $override = (int) round(((float) $validated['refund_override']) * 100);
        }

        $reason = self::cancellationReasons()[$validated['cancellation_reason']]
            .(filled($validated['reason_note'] ?? null) ? ' — '.$validated['reason_note'] : '');

        $groupBookings = $booking->booking_group_code
            ? Booking::query()->where('booking_group_code', $booking->booking_group_code)->orderBy('id')->get()
            : collect([$booking]);

        // Refunds are issued against the payment-owning booking so the queue
        // and the money trail stay on one row.
        $anchor = $groupBookings->first();

        $result = $cancellation->cancel($anchor, $groupBookings, $inventory, Booking::CANCELLED_BY_ADMIN, $reason, $override);

        $amount = fn (int $minor) => $anchor->currency.' '.number_format($minor / 100, 2);

        $status = match ($result['refund_state']) {
            Booking::REFUND_PROCESSED => 'Booking cancelled. '.$amount($result['refunded_minor']).' refunded to the guest\'s original payment method.',
            Booking::REFUND_FAILED, Booking::REFUND_MANUAL => 'Booking cancelled, but the refund of '.$amount($result['quote']['refund_due_minor']).' needs manual processing — see Pending refunds.',
            default => $result['quote']['paid_minor'] > 0
                ? 'Booking cancelled. No refund due per the cancellation policy'.($override !== null ? ' override' : '').'.'
                : 'Booking cancelled successfully.',
        };

        return redirect()
            ->route('admin.bookings.index')
            ->with('status', $status);
    }

    public function pendingRefunds(AdminPropertyScope $scope): View
    {
        $bookings = $scope->apply(Booking::query())
            ->with(['property', 'roomType'])
            ->whereIn('refund_state', [Booking::REFUND_FAILED, Booking::REFUND_MANUAL])
            ->orderBy('cancelled_at')
            ->paginate(15);

        return view('admin.bookings.pending-refunds', [
            'bookings' => $bookings,
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function settleRefund(Booking $booking, AdminPropertyScope $scope, CancellationService $cancellation): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);
        abort_unless(in_array($booking->refund_state, [Booking::REFUND_FAILED, Booking::REFUND_MANUAL], true), 404);

        $cancellation->markManuallyRefunded($booking);

        return redirect()
            ->route('admin.bookings.pending-refunds')
            ->with('status', 'Refund for '.$booking->booking_number.' marked as settled.');
    }

    /**
     * @return array<string, string>
     */
    public static function cancellationReasons(): array
    {
        return [
            'guest_request' => 'Guest requested',
            'no_show' => 'No-show',
            'property_issue' => 'Property issue',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function properties(AdminPropertyScope $scope): array
    {
        return $scope->properties()->pluck('name', 'id')->all();
    }

    /**
     * One selectable card per physical room: vacancy for the requested dates
     * plus pricing from the room type's default rate plan.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function roomCards(int $propertyId, CarbonImmutable $checkIn, CarbonImmutable $checkOut, ?int $ignoreBookingId, AvailabilityService $availability): \Illuminate\Support\Collection
    {
        $plans = [];

        return Room::query()
            ->with('roomType')
            ->where('property_id', $propertyId)
            ->whereHas('roomType', fn ($query) => $query->where('status', RoomType::STATUS_ACTIVE))
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get()
            ->map(function (Room $room) use ($propertyId, $checkIn, $checkOut, $ignoreBookingId, $availability, &$plans): array {
                $plan = $plans[$room->room_type_id] ??= RatePlan::defaultFor($propertyId, $room->room_type_id);
                $stayTotal = $plan ? $availability->quote($plan, $checkIn, $checkOut) : null;

                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'floor' => $room->floor,
                    'type' => $room->roomType?->name ?? 'Room',
                    'available' => $availability->roomIsAvailable($room, $checkIn, $checkOut, $ignoreBookingId),
                    'stay_total_minor' => $stayTotal,
                    'rate_label' => $plan ? $this->formatRate($plan->currency, $plan->default_price_minor).'/night' : 'No rate set',
                ];
            });
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, array<string, mixed>>>
     */
    private function roomBoardByFloor(AdminPropertyScope $scope, ?int $propertyId, CarbonImmutable $checkIn, CarbonImmutable $checkOut, ?int $ignoreBookingId, AvailabilityService $availability): \Illuminate\Support\Collection
    {
        if (! $propertyId || ! $scope->canAccessProperty($propertyId)) {
            return collect();
        }

        return $this->roomCards($propertyId, $checkIn, $checkOut, $ignoreBookingId, $availability)
            ->groupBy(fn (array $card) => $card['floor'] ? 'Floor '.$card['floor'] : 'Other rooms');
    }

    private function formatRate(string $currency, int $amountMinor): string
    {
        $symbol = match (strtoupper($currency)) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => strtoupper($currency).' ',
        };

        return $symbol.number_format($amountMinor / 100, 2);
    }

    /**
     * @return array<int, string>
     */
    private function guests(): array
    {
        return User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $guest) => [
                $guest->id => $guest->name.' - '.($guest->email ?: $guest->phone_e164 ?: $guest->phone ?: 'no contact'),
            ])
            ->all();
    }

    /**
     * Reuse an existing guest profile (matched by mobile or email) or create one,
     * so a typed-in walk-in guest can be found again on the next visit.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function resolveGuestProfile(array $attributes): ?User
    {
        $e164 = $this->normalizePhone($attributes['guest_phone'] ?? null);
        $email = $attributes['guest_email'] ?? null;

        $existing = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where(function ($query) use ($e164, $email): void {
                $query->when($e164, fn ($query) => $query->where('phone_e164', $e164)->orWhere('phone', $e164));
                $query->when($email, fn ($query) => $query->orWhere('email', $email));
            })
            ->when(! $e164 && ! $email, fn ($query) => $query->whereRaw('1 = 0'))
            ->first();

        if ($existing) {
            return $existing;
        }

        if (! $e164 && ! $email) {
            return null;
        }

        if ($email && User::query()->where('email', $email)->exists()) {
            $email = null;
        }

        $isIndian = $e164 && str_starts_with($e164, '+91') && strlen($e164) === 13;

        return User::query()->create([
            'name' => $attributes['guest_name'],
            'email' => $email,
            'password' => Str::random(40),
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
            'customer_type' => 'individual',
            'phone' => $e164,
            'phone_e164' => $e164,
            'phone_country_code' => $isIndian ? '+91' : null,
            'phone_national' => $isIndian ? substr($e164, 3) : null,
        ]);
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (preg_match('/^[6-9][0-9]{9}$/', $digits)) {
            return '+91'.$digits;
        }

        return strlen($digits) >= 11 && strlen($digits) <= 15 ? '+'.$digits : null;
    }

    private function touchGuestLastBooking(Booking $booking): void
    {
        $booking->user?->forceFill([
            'last_booking_at' => $booking->user->bookings()->max('check_in_date'),
        ])->save();
    }

    /**
     * Price fields for each selected room. A manually entered total is kept
     * verbatim (no discount, GST on the entered amount); otherwise each room
     * is quoted through the pricing service — with the booking's company when
     * one is selected (corporate rate is final), else automatic offers apply.
     *
     * @param  array<string, mixed>  $attributes
     * @param  \Illuminate\Support\Collection<int, int>  $roomIds
     * @param  \Illuminate\Support\Collection<int, Room>  $rooms
     * @param  \Illuminate\Support\Collection<int, ?RatePlan>  $plans
     * @return array<int, array<string, mixed>>
     */
    private function priceRooms(BookingRequest $request, array $attributes, $roomIds, $rooms, $plans, CarbonImmutable $checkIn, CarbonImmutable $checkOut, int $nights, PricingService $pricing): array
    {
        if ($request->filled('total_amount')) {
            return collect($this->splitTotal($attributes['total_amount_minor'], $roomIds->count()))
                ->map(function (int $amount) use ($nights): array {
                    $gst = Gst::forStay($amount, $nights);

                    return [
                        'total_amount_minor' => $amount,
                        'discount_amount_minor' => 0,
                        'discount_id' => null,
                        'discount_label' => null,
                        'tax_rate_bp' => $gst['rate_bp'],
                        'tax_amount_minor' => $gst['tax_minor'],
                    ];
                })
                ->all();
        }

        $corporate = ($attributes['corporate_id'] ?? null)
            ? Corporate::query()->with('roomRates')->find($attributes['corporate_id'])
            : null;

        return $roomIds->map(function (int $roomId) use ($rooms, $plans, $checkIn, $checkOut, $pricing, $corporate): array {
            $plan = $plans[$rooms[$roomId]->room_type_id];
            $quote = $plan ? $pricing->quotePlan($plan, $checkIn, $checkOut, null, $corporate) : null;
            $discount = $quote['discount'] ?? null;
            $discountMinor = $quote['discount_minor'] ?? 0;

            return [
                'total_amount_minor' => $quote['tariff_minor'] ?? 0,
                'discount_amount_minor' => $discountMinor,
                'discount_id' => $discount?->id,
                'discount_label' => $corporate && $discountMinor > 0
                    ? $corporate->displayName()
                    : $discount?->label(),
                'tax_rate_bp' => $quote['gst']['rate_bp'] ?? 0,
                'tax_amount_minor' => $quote['gst']['tax_minor'] ?? 0,
            ];
        })->all();
    }

    /**
     * @return array<int, array{name: string, default_billing: string}>
     */
    private function corporates(): array
    {
        return Corporate::query()
            ->where('is_active', true)
            ->orderBy('legal_name')
            ->get()
            ->mapWithKeys(fn (Corporate $corporate) => [$corporate->id => [
                'name' => $corporate->displayName(),
                'default_billing' => $corporate->default_billing ?: Booking::BILLING_GUEST,
            ]])
            ->all();
    }

    /**
     * Split a manually entered combined total evenly across the selected rooms.
     *
     * @return array<int, int>
     */
    private function splitTotal(int $totalMinor, int $roomCount): array
    {
        if ($roomCount <= 1) {
            return [$totalMinor];
        }

        $share = intdiv($totalMinor, $roomCount);
        $amounts = array_fill(0, $roomCount, $share);
        $amounts[0] += $totalMinor - ($share * $roomCount);

        return $amounts;
    }

    /**
     * Re-check availability while holding row locks on the selected rooms so two
     * simultaneous front-desk submissions cannot book the same room.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $roomIds
     * @param  array<string, mixed>  $attributes
     */
    private function lockAndRecheckRooms($roomIds, array $attributes, AvailabilityService $availability): void
    {
        if ($roomIds->isEmpty() || ! in_array($attributes['status'], Booking::blockingStatuses(), true)) {
            return;
        }

        $checkIn = CarbonImmutable::parse($attributes['check_in_date']);
        $checkOut = CarbonImmutable::parse($attributes['check_out_date']);

        foreach (Room::query()->whereKey($roomIds->all())->lockForUpdate()->get() as $room) {
            if (! $availability->roomIsAvailable($room, $checkIn, $checkOut)) {
                throw ValidationException::withMessages([
                    'room_ids' => "Room {$room->room_number} was booked by someone else a moment ago. Please pick a different room.",
                ]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function sources(): array
    {
        return (new BookingRequest)->sources();
    }
}
