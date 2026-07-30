<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Corporate;
use App\Models\Discount;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomImage;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\CancellationService;
use App\Services\Booking\InventoryService;
use App\Services\Booking\PricingService;
use App\Services\Payments\RazorpayGateway;
use App\Services\Rooms\AmenityResolver;
use App\Models\PropertyRoomType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookingEngineController extends Controller
{
    private const MAX_NIGHTS = 30;

    public function search(Request $request, AvailabilityService $availability, PricingService $pricing, AmenityResolver $amenityResolver): View
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

        $property?->load(['amenities' => fn ($query) => $query
            ->where('amenities.is_active', true)
            ->where('amenities.is_guest_visible', true)
            ->orderBy('amenities.sort_order')
            ->orderBy('amenities.name')]);
        $publishedRuleSet = $property?->publishedRulesFor($checkIn);

        $results = collect();
        $couponCode = strtoupper(trim($request->string('coupon')->toString()));
        $coupon = null;
        $corporate = null;
        $couponError = null;

        if ($property && $couponCode !== '') {
            // One box, two kinds of codes: a company booking code wins over a
            // coupon. Structural coupon checks only at search time — the
            // min-amount rule is enforced per plan row (and against the real
            // cart at store time).
            $resolvedCorporate = $pricing->resolveCorporate($couponCode);

            if ($resolvedCorporate instanceof Corporate) {
                $corporate = $resolvedCorporate;
            } else {
                $resolved = $pricing->resolveCoupon($couponCode, $property->id, $checkIn, $checkOut, PHP_INT_MAX);
                $resolved instanceof Discount ? $coupon = $resolved : $couponError = $resolved;
            }
        }

        if ($property && $checkIn->greaterThanOrEqualTo(CarbonImmutable::today())) {
            $results = PropertyRoomType::query()
                ->where('property_id', $property->id)
                ->where('status', 'active')
                ->whereHas('roomType', fn ($query) => $query->where('status', RoomType::STATUS_ACTIVE)
                    ->whereHas('rooms', fn ($rooms) => $rooms->where('property_id', $property->id)))
                ->with(['roomType' => fn ($query) => $query->with('images')])
                ->orderBy('sort_order')
                ->get()
                ->map(function (PropertyRoomType $configuration) use ($property, $availability, $pricing, $coupon, $corporate, $checkIn, $checkOut, $amenityResolver) {
                    $roomType = $configuration->roomType;
                    if (! $roomType) return null;
                    $roomType->setRelation('amenities', $amenityResolver->forCategory($property->id, $roomType->id)->where('is_guest_visible', true)->values());
                    $roomType->max_adults = $configuration->max_adults;
                    $roomType->max_children = $configuration->max_children;
                    $roomType->is_pet_friendly = $configuration->is_pet_friendly;
                    $roomType->extra_bed_available = $configuration->extra_bed_available;
                    $roomType->max_extra_beds = $configuration->max_extra_beds;
                    $roomType->extra_bed_charge_minor = $configuration->extra_bed_charge_minor;
                    $roomType->name = $configuration->display_name ?: $roomType->name;
                    $roomType->description = $configuration->description ?: $roomType->description;

                    $plans = RatePlan::query()->where('property_id', $property->id)->where('room_type_id', $roomType->id)
                        ->where('status', RatePlan::STATUS_ACTIVE)->orderBy('sort_order')->get();
                    $quotedPlans = $plans->map(function (RatePlan $plan) use ($pricing, $coupon, $corporate, $checkIn, $checkOut) {
                        $quote = $pricing->quotePlan($plan, $checkIn, $checkOut, $coupon, $corporate);
                        return $quote === null ? null : ['plan'=>$plan,'totalMinor'=>$quote['net_tariff_minor'],
                            'originalMinor'=>$quote['tariff_minor'],'discountMinor'=>$quote['discount_minor'],
                            'discountName'=>$corporate ? $corporate->displayName() : $quote['discount']?->name,'gst'=>$quote['gst']];
                    })->filter()->values();
                    $availableInventory = $availability->onlineTypeAvailability(
                        $property->id,
                        $roomType->id,
                        $checkIn,
                        $checkOut,
                    );
                    $onlineRoomCount = Room::query()->where('property_id', $property->id)->where('room_type_id', $roomType->id)->onlineBookable()->count();
                    $sellable = $onlineRoomCount > 0 && $quotedPlans->isNotEmpty() ? $availableInventory : 0;
                    $unavailableReason = $quotedPlans->isEmpty() ? 'Rates not configured'
                        : ($onlineRoomCount < 1 ? 'Not available online' : ($availableInventory < 1 ? 'Sold out for these dates' : null));
                    $galleryImages = RoomImage::query()->whereHas('room', fn ($query) => $query
                        ->where('property_id', $property->id)->where('room_type_id', $roomType->id))
                        ->inRandomOrder()->limit(3)->get();

                    return [
                        'roomType' => $roomType,
                        'sellable' => $sellable,
                        'onlineRoomCount' => $onlineRoomCount,
                        'unavailableReason' => $unavailableReason,
                        'galleryImages' => $galleryImages,
                        'plans' => $quotedPlans,
                    ];
                })
                ->filter(fn ($row) => is_array($row))
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
            'couponCode' => $couponCode,
            'coupon' => $coupon,
            'corporate' => $corporate,
            'couponError' => $couponError,
            'publishedRuleSet' => $publishedRuleSet,
        ]);
    }

    public function store(Request $request, AvailabilityService $availability, PricingService $pricing, InventoryService $inventory, CancellationService $cancellation): RedirectResponse
    {
        $validated = $request->validate([
            'rooms' => ['required', 'array'],
            'rooms.*' => ['integer', 'min:0', 'max:5'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'payment_mode' => ['required', Rule::in(['pay_online', 'pay_at_property', 'bill_to_company'])],
            // Required conditionally below only when this property has a live rule set.
            // Using Laravel's `accepted` rule here would reject legacy/no-rule bookings
            // when the checkbox is not present at all.
            'property_rules_accepted' => ['nullable', 'boolean'],
        ]);

        if ($request->user()?->hasRole(User::ROLE_CUSTOMER) !== true) {
            return back()
                ->withErrors(['auth' => 'Please sign in with your mobile number to complete the booking.'])
                ->withInput();
        }

        $selection = collect($validated['rooms'])
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn (int $qty) => $qty > 0);

        if ($selection->isEmpty()) {
            return back()->withErrors(['rooms' => 'Select at least one room to continue.'])->withInput();
        }

        $plans = RatePlan::query()
            ->with(['property', 'roomType'])
            ->whereIn('id', $selection->keys()->all())
            ->where('status', RatePlan::STATUS_ACTIVE)
            ->get();

        if ($plans->count() !== $selection->count() || $plans->pluck('property_id')->unique()->count() !== 1) {
            return back()->withErrors(['rooms' => 'Your selection is no longer valid — please search again.'])->withInput();
        }

        $property = $plans->first()->property;
        $checkIn = CarbonImmutable::parse($validated['check_in']);
        $checkOut = CarbonImmutable::parse($validated['check_out']);
        $nights = $checkIn->diffInDays($checkOut);
        $propertyRuleSet = $property->publishedRulesFor($checkIn);

        if ($propertyRuleSet && ! $request->boolean('property_rules_accepted')) {
            return back()->withErrors(['property_rules_accepted' => 'Please review and accept the property rules before booking.'])->withInput();
        }

        if ($property->status !== Property::STATUS_ACTIVE) {
            return back()->withErrors(['rooms' => 'This property is not accepting online bookings.'])->withInput();
        }

        if ($nights > self::MAX_NIGHTS) {
            return back()->withErrors(['check_out' => 'Online bookings are limited to '.self::MAX_NIGHTS.' nights. Please contact the property directly.'])->withInput();
        }

        foreach ($plans->groupBy('room_type_id') as $typePlans) {
            $wanted = $typePlans->sum(fn (RatePlan $plan) => $selection[$plan->id]);
            $sellable = $availability->onlineTypeAvailability($property->id, $typePlans->first()->room_type_id, $checkIn, $checkOut);

            if ($sellable < $wanted) {
                $typeName = $typePlans->first()->roomType->name;

                return back()->withErrors([
                    'rooms' => $sellable < 1
                        ? "Sorry, {$typeName} just sold out for your dates."
                        : "Only {$sellable} ".str('room')->plural($sellable)." of {$typeName} left for your dates — please adjust your selection.",
                ])->withInput();
            }
        }

        $quotes = [];
        foreach ($plans as $plan) {
            $quote = $availability->quote($plan, $checkIn, $checkOut);

            if ($quote === null || $quote < 1) {
                return back()->withErrors(['rooms' => "{$plan->name} is not bookable online for your dates."])->withInput();
            }

            $quotes[$plan->id] = $quote;
        }

        $coupon = null;
        $corporate = null;
        if (filled($validated['coupon_code'] ?? null)) {
            $resolvedCorporate = $pricing->resolveCorporate($validated['coupon_code']);

            if ($resolvedCorporate instanceof Corporate) {
                $corporate = $resolvedCorporate;
            } else {
                $cartTariff = $plans->sum(fn (RatePlan $plan) => $quotes[$plan->id] * $selection[$plan->id]);
                $resolved = $pricing->resolveCoupon($validated['coupon_code'], $property->id, $checkIn, $checkOut, $cartTariff);

                if (! $resolved instanceof Discount) {
                    return back()->withErrors(['coupon_code' => $resolved])->withInput();
                }

                // A category-scoped coupon must match at least one room in the
                // cart — tell the guest instead of silently ignoring it.
                if ($resolved->room_type_id !== null && ! $plans->contains('room_type_id', $resolved->room_type_id)) {
                    return back()->withErrors([
                        'coupon_code' => 'This coupon is valid only on '.($resolved->roomType?->name ?? 'a different room category').' rooms.',
                    ])->withInput();
                }

                $coupon = $resolved;
            }
        }

        if ($validated['payment_mode'] === 'bill_to_company' && ! $corporate) {
            return back()->withErrors(['payment_mode' => 'Bill to company needs a valid company booking code.'])->withInput();
        }

        $items = $plans->map(fn (RatePlan $plan) => ['plan' => $plan, 'qty' => $selection[$plan->id]])->values();
        $cart = $pricing->priceCart($items, $checkIn, $checkOut, $coupon, $corporate);

        if ($cart === null) {
            return back()->withErrors(['rooms' => 'Your selection is no longer bookable online — please search again.'])->withInput();
        }

        $user = $request->user();

        $booking = DB::transaction(function () use ($validated, $cart, $coupon, $corporate, $selection, $checkIn, $checkOut, $nights, $user, $inventory, $cancellation, $propertyRuleSet): Booking {
            // Re-check the coupon's usage limit while holding the row lock so
            // two simultaneous guests cannot both take the last use.
            if ($coupon && collect($cart['lines'])->contains(fn (array $line) => $line['discount']?->id === $coupon->id)) {
                $locked = Discount::query()->whereKey($coupon->id)->lockForUpdate()->first();

                if (! $locked || $locked->status !== Discount::STATUS_ACTIVE || ! $locked->hasUsesLeft()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'coupon_code' => 'This coupon was just fully used — please remove it and try again.',
                    ]);
                }
            }

            $groupCode = $selection->sum() > 1 ? (string) Str::uuid() : null;
            $created = collect();

            foreach ($cart['lines'] as $line) {
                $plan = $line['plan'];
                $configuration = PropertyRoomType::query()->with('amenities')->where('property_id', $plan->property_id)->where('room_type_id', $plan->room_type_id)->first();

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
                    'total_amount_minor' => $line['tariff_minor'],
                    'discount_amount_minor' => $line['discount_minor'],
                    'discount_id' => $line['discount']?->id,
                    'discount_label' => $corporate && $line['discount_minor'] > 0
                        ? $corporate->displayName()
                        : $line['discount']?->label(),
                    'corporate_id' => $corporate?->id,
                    'billing' => $validated['payment_mode'] === 'bill_to_company'
                        ? Booking::BILLING_CORPORATE
                        : Booking::BILLING_GUEST,
                    'tax_rate_bp' => $line['gst']['rate_bp'],
                    'tax_amount_minor' => $line['gst']['tax_minor'],
                    'payment_status' => Booking::PAYMENT_UNPAID,
                    'currency' => $plan->currency,
                    'special_requests' => $validated['special_requests'] ?? null,
                    'cancellation_policy_snapshot' => $cancellation->snapshotFor($plan, $checkIn),
                    'room_configuration_snapshot' => $configuration ? [
                        'room_type' => $configuration->displayName(),
                        'amenities' => $configuration->amenities->where('is_guest_visible', true)->pluck('name')->values()->all(),
                        'capacity' => ['adults' => $configuration->max_adults, 'children' => $configuration->max_children],
                        'pet_friendly' => $configuration->is_pet_friendly,
                        'extra_bed' => ['available' => $configuration->extra_bed_available, 'maximum' => $configuration->max_extra_beds, 'charge_minor' => $configuration->extra_bed_charge_minor, 'basis' => $configuration->extra_bed_charge_basis],
                    ] : null,
                    'property_rules_snapshot' => $propertyRuleSet ? array_merge($propertyRuleSet->snapshot(), [
                        'accepted_at' => now()->toIso8601String(),
                        'accepted_by_user_id' => $user?->id,
                        'channel' => 'online',
                    ]) : null,
                    'property_rules_version' => $propertyRuleSet?->version,
                    'booking_group_code' => $groupCode,
                ]);

                $inventory->syncBooking($booking);
                $created->push($booking);
            }

            // A group counts as one use per discount, coupon or automatic offer.
            collect($cart['lines'])
                ->map(fn (array $line) => $line['discount']?->id)
                ->filter()
                ->unique()
                ->each(fn (int $discountId) => Discount::query()->whereKey($discountId)->increment('times_used'));

            return $created->first();
        });

        if ($validated['payment_mode'] === 'pay_online') {
            return redirect()->route('book.pay', ['bookingNumber' => $booking->booking_number]);
        }

        return redirect()->route('book.confirmation', ['bookingNumber' => $booking->booking_number]);
    }

    /**
     * Payment page: creates (or reuses) a Razorpay order covering the whole
     * group's gross total and renders the checkout.
     */
    public function pay(string $bookingNumber, RazorpayGateway $gateway): View|RedirectResponse
    {
        $booking = $this->onlineBooking($bookingNumber);
        $groupBookings = $this->groupBookings($booking);

        if ($booking->payment_status === Booking::PAYMENT_PAID) {
            return redirect()->route('book.confirmation', ['bookingNumber' => $booking->booking_number]);
        }

        if ($booking->status === Booking::STATUS_CANCELLED) {
            return redirect()->route('book.search')->withErrors(['rooms' => 'This reservation has been cancelled.']);
        }

        $grossMinor = $groupBookings->sum(fn (Booking $room) => $room->grossTotalMinor());

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('status', Payment::STATUS_CREATED)
            ->latest('id')
            ->first();

        if (! $payment || $payment->amount_minor !== $grossMinor) {
            try {
                $order = $gateway->createOrder($grossMinor, $booking->currency, $booking->booking_number);
            } catch (\RuntimeException $exception) {
                return redirect()
                    ->route('book.confirmation', ['bookingNumber' => $booking->booking_number])
                    ->with('payment_error', $exception->getMessage());
            }

            $payment = Payment::query()->create([
                'booking_id' => $booking->id,
                'booking_group_code' => $booking->booking_group_code,
                'gateway' => 'razorpay',
                'gateway_order_id' => $order['id'],
                'status' => Payment::STATUS_CREATED,
                'amount_minor' => $grossMinor,
                'currency' => $booking->currency,
            ]);
        }

        return view('public.booking.pay', [
            'booking' => $booking,
            'groupBookings' => $groupBookings,
            'payment' => $payment,
            'razorpayKey' => $gateway->keyId(),
            'localSandbox' => $gateway->isLocalSandbox(),
            'sandboxPaymentId' => $gateway->isLocalSandbox() ? 'pay_local_'.Str::lower(Str::random(18)) : null,
        ]);
    }

    public function verifyPayment(Request $request, RazorpayGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = Payment::query()
            ->where('gateway_order_id', $validated['razorpay_order_id'])
            ->with('booking')
            ->firstOrFail();

        $booking = $payment->booking;

        if ($payment->status === Payment::STATUS_CAPTURED) {
            return redirect()->route('book.confirmation', ['bookingNumber' => $booking->booking_number]);
        }

        if (! $gateway->signatureIsValid($validated['razorpay_order_id'], $validated['razorpay_payment_id'], $validated['razorpay_signature'])) {
            $payment->update(['status' => Payment::STATUS_FAILED, 'failure_reason' => 'Signature verification failed.']);

            return redirect()
                ->route('book.pay', ['bookingNumber' => $booking->booking_number])
                ->withErrors(['payment' => 'We could not verify this payment. If money was deducted it will be auto-refunded by the gateway — please try again.']);
        }

        DB::transaction(function () use ($payment, $validated, $booking): void {
            $payment->update([
                'gateway_payment_id' => $validated['razorpay_payment_id'],
                'gateway_signature' => $validated['razorpay_signature'],
                'status' => Payment::STATUS_CAPTURED,
                'paid_at' => now(),
            ]);

            foreach ($this->groupBookings($booking) as $room) {
                $room->update([
                    'status' => Booking::STATUS_CONFIRMED,
                    'payment_status' => Booking::PAYMENT_PAID,
                    'invoice_number' => Booking::nextInvoiceNumber(),
                ]);
            }
        });

        return redirect()
            ->route('book.confirmation', ['bookingNumber' => $booking->booking_number])
            ->with('status', 'Payment received — your booking is confirmed!');
    }

    public function confirmation(string $bookingNumber, CancellationService $cancellation): View
    {
        $booking = $this->onlineBooking($bookingNumber);
        $groupBookings = $this->groupBookings($booking);

        return view('public.booking.confirmation', [
            'booking' => $booking,
            'groupBookings' => $groupBookings,
            'payment' => Payment::query()
                ->where('booking_id', $booking->id)
                ->where('status', Payment::STATUS_CAPTURED)
                ->latest('id')
                ->first(),
            'policyLines' => $this->policyLines($booking),
        ]);
    }

    public function cancelForm(string $bookingNumber, CancellationService $cancellation): View
    {
        $booking = $this->onlineBooking($bookingNumber);
        $groupBookings = $this->groupBookings($booking);

        return view('public.booking.cancel', [
            'booking' => $booking,
            'groupBookings' => $groupBookings,
            'quote' => $cancellation->quote($booking, $groupBookings),
            'policyLines' => $this->policyLines($booking),
        ]);
    }

    public function cancel(Request $request, string $bookingNumber, InventoryService $inventory, CancellationService $cancellation): RedirectResponse
    {
        $validated = $request->validate([
            'guest_phone' => ['required', 'string', 'max:40'],
        ]);

        $booking = $this->onlineBooking($bookingNumber);
        $groupBookings = $this->groupBookings($booking);

        $digits = fn (string $phone) => substr(preg_replace('/\D+/', '', $phone), -10);
        if ($digits($validated['guest_phone']) !== $digits((string) $booking->guest_phone)) {
            return back()->withErrors(['guest_phone' => 'This phone number does not match the booking.']);
        }

        if ($booking->status === Booking::STATUS_CANCELLED) {
            return back()->withErrors(['guest_phone' => 'This reservation is already cancelled.']);
        }

        if (in_array($booking->status, [Booking::STATUS_CHECKED_IN, Booking::STATUS_CHECKED_OUT], true)) {
            return back()->withErrors(['guest_phone' => 'This stay has already started — please contact the property.']);
        }

        $result = $cancellation->cancel($booking, $groupBookings, $inventory, Booking::CANCELLED_BY_GUEST, 'Cancelled by guest online');

        $amount = fn (int $minor) => $booking->currency.' '.number_format($minor / 100, 2);

        $message = match (true) {
            $result['refund_state'] === Booking::REFUND_PROCESSED => 'Your booking is cancelled. '
                .($result['quote']['refund_percent'] >= 100 ? 'The full amount' : 'As per the cancellation policy, '.$result['quote']['refund_percent'].'% of the amount paid')
                .' — '.$amount($result['refunded_minor']).' — will be refunded to your original payment method in 5–7 working days.',
            in_array($result['refund_state'], [Booking::REFUND_FAILED, Booking::REFUND_MANUAL], true) => 'Your booking is cancelled. Your refund of '.$amount($result['quote']['refund_due_minor']).' will be processed manually by the property — you will be contacted on '.$booking->guest_phone.'.',
            $result['quote']['paid_minor'] > 0 => 'Your booking is cancelled. As per the rate\'s cancellation policy, no refund is due for cancelling at this time.',
            default => 'Your booking has been cancelled.',
        };

        return redirect()
            ->route('book.confirmation', ['bookingNumber' => $booking->booking_number])
            ->with('status', $message);
    }

    private function onlineBooking(string $bookingNumber): Booking
    {
        return Booking::query()
            ->where('booking_number', $bookingNumber)
            ->where('source', Booking::SOURCE_ONLINE)
            ->with(['property', 'roomType', 'ratePlan', 'corporate'])
            ->firstOrFail();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Booking>
     */
    private function groupBookings(Booking $booking)
    {
        return $booking->booking_group_code
            ? Booking::query()
                ->where('booking_group_code', $booking->booking_group_code)
                ->with(['roomType', 'ratePlan'])
                ->orderBy('id')
                ->get()
            : collect([$booking]);
    }

    /**
     * Guest-facing policy timeline from the booking's frozen snapshot, e.g.
     * "Cancel before 2:00 PM, 14 Jul — 100% refund". Legacy bookings without
     * a snapshot get the wording they were sold under.
     *
     * @return list<string>
     */
    private function policyLines(Booking $booking): array
    {
        $snapshot = $booking->cancellation_policy_snapshot;

        if (! is_array($snapshot)) {
            return (bool) $booking->ratePlan?->is_refundable
                ? ['Free cancellation before '.$booking->check_in_date->format('j M Y'), 'On or after that — no refund']
                : ['This reservation is non-refundable'];
        }

        $lines = [];

        foreach ($snapshot['tiers'] ?? [] as $tier) {
            $lines[] = sprintf(
                'Cancel before %s — %s refund',
                CarbonImmutable::parse($tier['until'])->format('g:i A, j M Y'),
                $tier['refund_percent'] >= 100 ? 'full' : $tier['refund_percent'].'%',
            );
        }

        $lines[] = $lines === [] ? 'This reservation is non-refundable' : 'After that — no refund';

        return $lines;
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
    }
}
