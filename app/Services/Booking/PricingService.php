<?php

namespace App\Services\Booking;

use App\Models\Corporate;
use App\Models\Discount;
use App\Models\RatePlan;
use App\Services\Payments\Gst;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The single place where tariff, discounts and GST are combined, so the price
 * shown on search, stored on the booking and charged at the gateway always
 * agree. Discounts never stack: the best single one (guest's coupon vs best
 * automatic offer) wins, ties going to the coupon.
 */
class PricingService
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    /**
     * Quote one rate plan for a stay, discount and GST included. When a
     * corporate is given its negotiated pricing replaces coupons and offers
     * entirely — the corporate rate is final.
     *
     * @return array{tariff_minor: int, discount_minor: int, discount: ?Discount, corporate: ?Corporate,
     *     net_tariff_minor: int, gst: array{rate_bp: int, tax_minor: int, cgst_minor: int, sgst_minor: int},
     *     gross_minor: int}|null null when the plan is not quotable for these dates
     */
    public function quotePlan(RatePlan $plan, CarbonInterface $checkIn, CarbonInterface $checkOut, ?Discount $coupon = null, ?Corporate $corporate = null): ?array
    {
        $tariff = $this->availability->quote($plan, $checkIn, $checkOut);

        if ($tariff === null || $tariff < 1) {
            return null;
        }

        if ($corporate) {
            $discountMinor = $this->corporateDiscountFor($corporate, $plan, $tariff, $checkIn, $checkOut);

            return $this->priceLine($tariff, $discountMinor, null, $checkIn, $checkOut, $corporate);
        }

        $discount = $this->bestDiscount($plan->property_id, $checkIn, $checkOut, $tariff, $coupon, $plan->room_type_id);
        $discountMinor = $discount?->discountFor($tariff) ?? 0;

        return $this->priceLine($tariff, $discountMinor, $discount, $checkIn, $checkOut);
    }

    /**
     * Price a whole cart for store(): one line per physical room. A coupon is
     * validated against the cart tariff and its discount split across the room
     * lines so per-room amounts add up to the cart discount exactly.
     *
     * @param  Collection<int, array{plan: RatePlan, qty: int}>  $items
     * @return array{lines: list<array{plan: RatePlan, tariff_minor: int, discount_minor: int,
     *     discount: ?Discount, net_tariff_minor: int,
     *     gst: array{rate_bp: int, tax_minor: int, cgst_minor: int, sgst_minor: int},
     *     gross_minor: int}>, gross_total_minor: int, total_discount_minor: int}|null
     *     null when any plan is not quotable
     */
    public function priceCart(Collection $items, CarbonInterface $checkIn, CarbonInterface $checkOut, ?Discount $coupon = null, ?Corporate $corporate = null): ?array
    {
        $rooms = collect();

        foreach ($items as $item) {
            $tariff = $this->availability->quote($item['plan'], $checkIn, $checkOut);

            if ($tariff === null || $tariff < 1) {
                return null;
            }

            foreach (range(1, $item['qty']) as $ignored) {
                $rooms->push(['plan' => $item['plan'], 'tariff' => $tariff]);
            }
        }

        if ($corporate) {
            // Corporate rate is final: no coupon split, no automatic offers.
            $lines = $rooms->map(function (array $room) use ($checkIn, $checkOut, $corporate) {
                $discountMinor = $this->corporateDiscountFor($corporate, $room['plan'], $room['tariff'], $checkIn, $checkOut);

                return ['plan' => $room['plan']] + $this->priceLine($room['tariff'], $discountMinor, null, $checkIn, $checkOut, $corporate);
            })->values();

            return [
                'lines' => $lines->all(),
                'gross_total_minor' => $lines->sum('gross_minor'),
                'total_discount_minor' => $lines->sum('discount_minor'),
            ];
        }

        $cartTariff = $rooms->sum('tariff');
        $couponShares = $this->splitCouponAcrossRooms($rooms, $cartTariff, $checkIn, $checkOut, $coupon);

        $lines = $rooms->map(function (array $room, int $index) use ($checkIn, $checkOut, $coupon, $couponShares) {
            $offer = $this->bestOffer($room['plan']->property_id, $checkIn, $checkOut, $room['tariff'], $room['plan']->room_type_id);
            $offerMinor = $offer?->discountFor($room['tariff']) ?? 0;
            $couponMinor = $couponShares[$index] ?? 0;

            // Best single discount wins per room; ties go to the coupon.
            [$discount, $discountMinor] = $couponMinor >= $offerMinor && $coupon
                ? [$couponMinor > 0 ? $coupon : null, $couponMinor]
                : [$offerMinor > 0 ? $offer : null, $offerMinor];

            return ['plan' => $room['plan']] + $this->priceLine($room['tariff'], $discountMinor, $discount, $checkIn, $checkOut);
        })->values();

        return [
            'lines' => $lines->all(),
            'gross_total_minor' => $lines->sum('gross_minor'),
            'total_discount_minor' => $lines->sum('discount_minor'),
        ];
    }

    /**
     * Look up a coupon code for the given stay.
     *
     * @return Discount|string the coupon, or a guest-facing error message
     */
    public function resolveCoupon(string $code, int $propertyId, CarbonInterface $checkIn, CarbonInterface $checkOut, int $cartTariffMinor): Discount|string
    {
        $coupon = Discount::query()
            ->whereNotNull('code')
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $coupon || $coupon->status !== Discount::STATUS_ACTIVE) {
            return 'This coupon code is not valid.';
        }

        if ($coupon->property_id !== null && $coupon->property_id !== $propertyId) {
            return 'This coupon cannot be used at this property.';
        }

        if ($coupon->valid_from !== null && $checkIn->lessThan($coupon->valid_from)) {
            return 'This coupon is valid for stays from '.$coupon->valid_from->format('j M Y').'.';
        }

        if ($coupon->valid_until !== null && $checkIn->greaterThan($coupon->valid_until)) {
            return 'This coupon has expired.';
        }

        $nights = max(1, $checkIn->diffInDays($checkOut));
        if ($coupon->min_nights !== null && $nights < $coupon->min_nights) {
            return 'This coupon needs a stay of at least '.$coupon->min_nights.' '.str('night')->plural($coupon->min_nights).'.';
        }

        if ($coupon->min_amount_minor !== null && $cartTariffMinor < $coupon->min_amount_minor) {
            return 'This coupon needs a booking of at least ₹'.number_format($coupon->min_amount_minor / 100).'.';
        }

        if (! $coupon->hasUsesLeft()) {
            return 'This coupon has been fully used.';
        }

        return $coupon;
    }

    /**
     * Look up a company booking code.
     *
     * @return Corporate|string the company, or a guest-facing error message
     */
    public function resolveCorporate(string $code): Corporate|string
    {
        $corporate = Corporate::query()
            ->with('roomRates')
            ->whereNotNull('booking_code')
            ->where('booking_code', strtoupper(trim($code)))
            ->first();

        if (! $corporate || ! $corporate->is_active) {
            return 'This code is not valid.';
        }

        return $corporate;
    }

    /**
     * The company's saving on one room: a negotiated flat nightly price for
     * the room type wins; otherwise the blanket discount; otherwise nothing.
     * A negotiated price above the normal rate clamps to zero — the guest
     * simply pays the normal rate.
     */
    private function corporateDiscountFor(Corporate $corporate, RatePlan $plan, int $tariffMinor, CarbonInterface $checkIn, CarbonInterface $checkOut): int
    {
        $nightly = $corporate->nightlyRateFor($plan->room_type_id);

        if ($nightly !== null) {
            $nights = max(1, $checkIn->diffInDays($checkOut));

            return max(0, $tariffMinor - $nightly * $nights);
        }

        return $corporate->blanketDiscountFor($tariffMinor);
    }

    /** Best automatic (code-less) offer for the stay, or null. */
    public function bestOffer(int $propertyId, CarbonInterface $checkIn, CarbonInterface $checkOut, int $tariffMinor, ?int $roomTypeId = null): ?Discount
    {
        return Discount::query()
            ->whereNull('code')
            ->where('status', Discount::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('property_id')->orWhere('property_id', $propertyId))
            ->get()
            ->filter(fn (Discount $offer) => $offer->appliesTo($propertyId, $checkIn, $checkOut, $tariffMinor, $roomTypeId))
            ->sortByDesc(fn (Discount $offer) => $offer->discountFor($tariffMinor))
            ->first();
    }

    /** Best of the guest's coupon vs the best automatic offer; ties go to the coupon. */
    private function bestDiscount(int $propertyId, CarbonInterface $checkIn, CarbonInterface $checkOut, int $tariffMinor, ?Discount $coupon, ?int $roomTypeId = null): ?Discount
    {
        $couponMinor = $coupon && $coupon->appliesTo($propertyId, $checkIn, $checkOut, $tariffMinor, $roomTypeId)
            ? $coupon->discountFor($tariffMinor)
            : 0;

        $offer = $this->bestOffer($propertyId, $checkIn, $checkOut, $tariffMinor, $roomTypeId);
        $offerMinor = $offer?->discountFor($tariffMinor) ?? 0;

        if ($couponMinor < 1 && $offerMinor < 1) {
            return null;
        }

        return $couponMinor >= $offerMinor ? $coupon : $offer;
    }

    /**
     * @return array{tariff_minor: int, discount_minor: int, discount: ?Discount, corporate: ?Corporate,
     *     net_tariff_minor: int, gst: array{rate_bp: int, tax_minor: int, cgst_minor: int, sgst_minor: int},
     *     gross_minor: int}
     */
    private function priceLine(int $tariff, int $discountMinor, ?Discount $discount, CarbonInterface $checkIn, CarbonInterface $checkOut, ?Corporate $corporate = null): array
    {
        $net = max(0, $tariff - $discountMinor);
        // GST on the discounted amount — a discount can move the per-night
        // price below the slab threshold, so never reuse a pre-discount rate.
        $gst = Gst::forStay($net, max(1, $checkIn->diffInDays($checkOut)));

        return [
            'tariff_minor' => $tariff,
            'discount_minor' => $discountMinor,
            'discount' => $discountMinor > 0 ? $discount : null,
            'corporate' => $corporate,
            'net_tariff_minor' => $net,
            'gst' => $gst,
            'gross_minor' => $net + $gst['tax_minor'],
        ];
    }

    /**
     * The coupon discount is computed on the eligible cart tariff, then
     * divided across the eligible rooms in proportion to each room's tariff
     * with the rounding remainder going to the first of them, so the shares
     * add up exactly. A category-scoped coupon skips rooms of other types —
     * those lines can still win an automatic offer of their own.
     *
     * @param  Collection<int, array{plan: RatePlan, tariff: int}>  $rooms
     * @return array<int, int>
     */
    private function splitCouponAcrossRooms(Collection $rooms, int $cartTariff, CarbonInterface $checkIn, CarbonInterface $checkOut, ?Discount $coupon): array
    {
        $propertyId = $rooms->first()['plan']->property_id ?? 0;

        if (! $coupon || $cartTariff < 1) {
            return [];
        }

        $eligible = $rooms->filter(
            fn (array $room) => $coupon->room_type_id === null || $coupon->room_type_id === $room['plan']->room_type_id
        );
        $eligibleTariff = $eligible->sum('tariff');

        // Structural checks (dates, min nights, min amount) run against the
        // eligible portion of the cart — what the coupon actually discounts.
        if ($eligibleTariff < 1 || ! $coupon->appliesTo($propertyId, $checkIn, $checkOut, $eligibleTariff)) {
            return [];
        }

        $cartDiscount = $coupon->discountFor($eligibleTariff);
        $shares = [];
        $allocated = 0;

        foreach ($eligible as $index => $room) {
            $shares[$index] = intdiv($cartDiscount * $room['tariff'], $eligibleTariff);
            $allocated += $shares[$index];
        }

        foreach (array_keys($shares) as $index) {
            if ($allocated >= $cartDiscount) {
                break;
            }
            $shares[$index]++;
            $allocated++;
        }

        return $shares;
    }
}
