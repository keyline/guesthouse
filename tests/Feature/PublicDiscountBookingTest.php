<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicDiscountBookingTest extends TestCase
{
    use RefreshDatabase;

    private ?User $bookingCustomer = null;

    // -------------------------------------------------- pricing maths

    public function test_percentage_discount_with_cap_and_fixed_discount(): void
    {
        $percent = new Discount(['discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'max_discount_minor' => 15000]);
        $this->assertSame(10000, $percent->discountFor(100000)); // 10% of ₹1,000
        $this->assertSame(15000, $percent->discountFor(500000)); // capped at ₹150

        $fixed = new Discount(['discount_type' => Discount::TYPE_FIXED, 'discount_value' => 50000]);
        $this->assertSame(50000, $fixed->discountFor(100000));
        $this->assertSame(30000, $fixed->discountFor(30000)); // never above the tariff
    }

    public function test_discount_can_flip_gst_slab_below_threshold(): void
    {
        // ₹8,000/night is in the 18% slab; 10% off makes it ₹7,200 → 5% slab.
        [$property, , $plan] = $this->sellableSetup(priceMinor: 800000);
        $coupon = $this->coupon($property, ['discount_value' => 1000]);

        $quote = app(PricingService::class)->quotePlan(
            $plan->fresh(),
            now()->toImmutable()->startOfDay(),
            now()->toImmutable()->addDay()->startOfDay(),
            $coupon,
        );

        $this->assertSame(80000, $quote['discount_minor']);
        $this->assertSame(720000, $quote['net_tariff_minor']);
        $this->assertSame(500, $quote['gst']['rate_bp']);
        $this->assertSame(720000 + 36000, $quote['gross_minor']);
    }

    public function test_cart_discount_splits_exactly_across_rooms(): void
    {
        [$property, , $plan] = $this->sellableSetup(totalRooms: 3);
        $coupon = $this->coupon($property, ['discount_type' => Discount::TYPE_FIXED, 'discount_value' => 100000]);

        $cart = app(PricingService::class)->priceCart(
            collect([['plan' => $plan->fresh(), 'qty' => 3]]),
            now()->toImmutable()->startOfDay(),
            now()->toImmutable()->addDay()->startOfDay(),
            $coupon,
        );

        $this->assertSame(100000, $cart['total_discount_minor']);
        $this->assertSame(100000, collect($cart['lines'])->sum('discount_minor'));
        $this->assertCount(3, $cart['lines']);
    }

    public function test_best_single_discount_wins_and_tie_goes_to_coupon(): void
    {
        [$property, , $plan] = $this->sellableSetup();
        $this->offer($property, ['name' => 'Big Offer', 'discount_value' => 2000]); // 20%
        $coupon = $this->coupon($property, ['discount_value' => 1000]); // 10%

        $quote = app(PricingService::class)->quotePlan(
            $plan->fresh(),
            now()->toImmutable()->startOfDay(),
            now()->toImmutable()->addDay()->startOfDay(),
            $coupon,
        );

        // Offer (20%) beats coupon (10%) — they do not stack.
        $this->assertSame('Big Offer', $quote['discount']->name);
        $this->assertSame((int) round(180000 * 0.20), $quote['discount_minor']);

        $tieCoupon = $this->coupon($property, ['code' => 'TIE20', 'discount_value' => 2000]);
        $tieQuote = app(PricingService::class)->quotePlan(
            $plan->fresh(),
            now()->toImmutable()->startOfDay(),
            now()->toImmutable()->addDay()->startOfDay(),
            $tieCoupon,
        );

        $this->assertSame('TIE20', $tieQuote['discount']->code);
    }

    // -------------------------------------------------- search page

    public function test_valid_coupon_shows_savings_on_search(): void
    {
        [$property] = $this->sellableSetup();
        $this->coupon($property, ['discount_value' => 1000]);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString().'&coupon=SAVE10')
            ->assertOk()
            ->assertSee('Coupon')
            ->assertSee('SAVE10')
            ->assertSee('You save');
    }

    public function test_invalid_coupon_shows_error_on_search(): void
    {
        [$property] = $this->sellableSetup();

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString().'&coupon=NOPE')
            ->assertOk()
            ->assertSee('This coupon code is not valid.');
    }

    public function test_automatic_offer_applies_without_a_code(): void
    {
        [$property] = $this->sellableSetup();
        $this->offer($property, ['name' => 'Long Stay Deal', 'discount_value' => 1500, 'min_nights' => 2]);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDays(2)->toDateString())
            ->assertOk()
            ->assertSee('You save')
            ->assertSee('Long Stay Deal');

        // One night misses the min-nights condition — no discount shown.
        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertDontSee('Long Stay Deal');
    }

    // -------------------------------------------------- booking flow

    public function test_coupon_booking_persists_discount_and_counts_a_use(): void
    {
        [$property, , $plan] = $this->sellableSetup();
        $coupon = $this->coupon($property, ['discount_value' => 1000, 'max_uses' => 5]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, ['coupon_code' => 'SAVE10']))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        // 2 nights × ₹1,800 = ₹3,600 tariff, 10% off = ₹360, GST 5% on ₹3,240.
        $this->assertSame(360000, $booking->total_amount_minor);
        $this->assertSame(36000, $booking->discount_amount_minor);
        $this->assertSame($coupon->id, $booking->discount_id);
        $this->assertSame('SAVE10', $booking->discount_label);
        $this->assertSame(500, $booking->tax_rate_bp);
        $this->assertSame((int) round(324000 * 0.05), $booking->tax_amount_minor);
        $this->assertSame(324000 + $booking->tax_amount_minor, $booking->grossTotalMinor());
        $this->assertSame(1, $coupon->fresh()->times_used);
    }

    public function test_exhausted_coupon_is_rejected(): void
    {
        [$property, , $plan] = $this->sellableSetup();
        $this->coupon($property, ['discount_value' => 1000, 'max_uses' => 1, 'times_used' => 1]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, ['coupon_code' => 'SAVE10']))
            ->assertSessionHasErrors('coupon_code');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_coupon_below_minimum_amount_is_rejected_at_store(): void
    {
        [$property, , $plan] = $this->sellableSetup();
        $this->coupon($property, ['discount_value' => 1000, 'min_amount_minor' => 999999900]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, ['coupon_code' => 'SAVE10']))
            ->assertSessionHasErrors('coupon_code');
    }

    public function test_automatic_offer_recorded_on_booking_without_code(): void
    {
        [$property, , $plan] = $this->sellableSetup();
        $offer = $this->offer($property, ['name' => 'Monsoon Special', 'discount_value' => 2000]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        $this->assertSame(72000, $booking->discount_amount_minor); // 20% of ₹3,600
        $this->assertSame($offer->id, $booking->discount_id);
        $this->assertSame('Monsoon Special', $booking->discount_label);
        $this->assertSame(1, $offer->fresh()->times_used);
    }

    public function test_cancelling_releases_the_coupon_use(): void
    {
        [$property, , $plan] = $this->sellableSetup();
        $coupon = $this->coupon($property, ['discount_value' => 1000, 'max_uses' => 1]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, ['coupon_code' => 'SAVE10']))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(1, $coupon->fresh()->times_used);

        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), [
            'guest_phone' => '+91 98000 00000',
        ])->assertRedirect();

        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
        $this->assertSame(0, $coupon->fresh()->times_used);
    }

    public function test_online_payment_order_uses_discounted_gross(): void
    {
        Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_TEST123'])]);
        config(['services.razorpay.key_id' => 'rzp_test_x', 'services.razorpay.key_secret' => 'secret']);

        [$property, , $plan] = $this->sellableSetup();
        $this->coupon($property, ['discount_value' => 1000]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, ['coupon_code' => 'SAVE10', 'payment_mode' => 'pay_online']))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->get(route('book.pay', ['bookingNumber' => $booking->booking_number]))->assertOk();

        $payment = Payment::query()->firstOrFail();
        $this->assertSame($booking->grossTotalMinor(), $payment->amount_minor);
        $this->assertSame(324000 + (int) round(324000 * 0.05), $payment->amount_minor);
    }

    // -------------------------------------------------- category scoping

    public function test_room_type_scoped_offer_skips_other_categories(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();
        [$otherType, $otherPlan] = $this->secondRoomType($property);

        $this->offer($property, ['room_type_id' => $roomType->id]);

        // The other category books at full price…
        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($otherPlan))
            ->assertSessionHasNoErrors();
        $this->assertSame(0, Booking::query()->firstOrFail()->discount_amount_minor);

        // …while the scoped category gets the offer automatically.
        $this->post('/book', $this->reservePayload($plan))->assertSessionHasNoErrors();
        $discounted = Booking::query()->where('room_type_id', $roomType->id)->firstOrFail();
        $this->assertSame((int) round($discounted->total_amount_minor * 0.10), $discounted->discount_amount_minor);
    }

    public function test_scoped_coupon_discounts_only_matching_lines_in_a_mixed_cart(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();
        [, $otherPlan] = $this->secondRoomType($property);

        $this->coupon($property, ['room_type_id' => $roomType->id, 'discount_value' => 1000]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, [
                'rooms' => [$plan->id => 1, $otherPlan->id => 1],
                'coupon_code' => 'SAVE10',
            ]))
            ->assertSessionHasNoErrors();

        $matching = Booking::query()->where('rate_plan_id', $plan->id)->firstOrFail();
        $other = Booking::query()->where('rate_plan_id', $otherPlan->id)->firstOrFail();

        // 10% off the eligible line only; the other line stays full price.
        $this->assertSame((int) round($matching->total_amount_minor * 0.10), $matching->discount_amount_minor);
        $this->assertSame(0, $other->discount_amount_minor);
        $this->assertSame(1, Discount::query()->firstOrFail()->times_used);
    }

    public function test_coupon_matching_no_cart_line_is_rejected_with_a_clear_message(): void
    {
        [$property] = $this->sellableSetup();
        [, $otherPlan] = $this->secondRoomType($property);

        $lonelyType = RoomType::query()->create([
            'name' => 'Suite', 'code' => 'STE', 'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 3, 'max_children' => 2, 'sort_order' => 5,
        ]);
        $this->coupon($property, ['room_type_id' => $lonelyType->id]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($otherPlan, ['coupon_code' => 'SAVE10']))
            ->assertSessionHasErrors('coupon_code');

        $this->assertSame(0, Booking::query()->count());
    }

    // -------------------------------------------------- fixtures

    private function customer(): User
    {
        return $this->bookingCustomer ??= User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true]);
    }

    /**
     * A second sellable category at the same property, same nightly price.
     *
     * @return array{0: RoomType, 1: RatePlan}
     */
    private function secondRoomType(Property $property, int $priceMinor = 180000): array
    {
        $roomType = RoomType::query()->create([
            'name' => 'Standard Single', 'code' => 'SGL', 'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 1, 'max_children' => 0, 'sort_order' => 1,
        ]);

        Room::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'room_number' => '201', 'status' => Room::STATUS_AVAILABLE, 'is_online_bookable' => true,
        ]);

        $plan = RatePlan::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)', 'code' => 'STD-EP-SGL', 'meal_plan' => RatePlan::MEAL_PLAN_EP,
            'is_refundable' => true, 'default_price_minor' => $priceMinor,
            'currency' => 'INR', 'status' => RatePlan::STATUS_ACTIVE,
        ]);

        return [$roomType, $plan];
    }

    private function reservePayload(RatePlan $plan, array $overrides = []): array
    {
        return array_merge([
            'rooms' => [$plan->id => 1],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'guest_email' => 'priya@example.com',
            'adults' => 2,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
        ], $overrides);
    }

    private function coupon(Property $property, array $overrides = []): Discount
    {
        return Discount::query()->create(array_merge([
            'property_id' => $property->id,
            'code' => 'SAVE10',
            'name' => 'Welcome Saver',
            'discount_type' => Discount::TYPE_PERCENT,
            'discount_value' => 1000,
            'status' => Discount::STATUS_ACTIVE,
        ], $overrides));
    }

    private function offer(Property $property, array $overrides = []): Discount
    {
        return Discount::query()->create(array_merge([
            'property_id' => $property->id,
            'code' => null,
            'name' => 'Automatic Offer',
            'discount_type' => Discount::TYPE_PERCENT,
            'discount_value' => 1000,
            'status' => Discount::STATUS_ACTIVE,
        ], $overrides));
    }

    private function sellableSetup(int $totalRooms = 2, int $priceMinor = 180000): array
    {
        $property = Property::query()->create([
            'name' => 'Central Guest House',
            'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE,
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'country' => 'India',
            'postal_code' => '700001',
            'address' => '12 Guest Road',
            'phone' => '+91 90000 00000',
            'email' => 'property@example.com',
            'manager_name' => 'Front Office Manager',
            'check_in_time_minutes' => 720,
            'check_out_time_minutes' => 660,
            'base_price_minor' => $priceMinor,
            'currency' => 'INR',
            'sort_order' => 0,
        ]);

        $roomType = RoomType::query()->create([
            'name' => 'Deluxe Double',
            'code' => 'DLX',
            'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2,
            'max_children' => 1,
            'sort_order' => 0,
        ]);

        foreach (range(1, $totalRooms) as $index) {
            Room::query()->create([
                'property_id' => $property->id,
                'room_type_id' => $roomType->id,
                'room_number' => (string) (100 + $index),
                'status' => Room::STATUS_AVAILABLE,
                'is_online_bookable' => true,
            ]);
        }

        $plan = RatePlan::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)',
            'code' => 'STD-EP',
            'meal_plan' => RatePlan::MEAL_PLAN_EP,
            'is_refundable' => true,
            'default_price_minor' => $priceMinor,
            'currency' => 'INR',
            'status' => RatePlan::STATUS_ACTIVE,
        ]);

        return [$property, $roomType, $plan];
    }
}
