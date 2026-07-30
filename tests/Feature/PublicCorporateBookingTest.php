<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Corporate;
use App\Models\DailyRate;
use App\Models\Discount;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCorporateBookingTest extends TestCase
{
    use RefreshDatabase;

    private ?User $bookingCustomer = null;

    // -------------------------------------------------- pricing

    public function test_negotiated_room_rate_beats_blanket_discount(): void
    {
        [, , $plan] = $this->sellableSetup(); // ₹1,800/night
        $corporate = $this->corporate([
            'discount_type' => Discount::TYPE_PERCENT,
            'discount_value' => 1000, // 10% blanket
        ]);
        $corporate->roomRates()->create(['room_type_id' => $plan->room_type_id, 'price_minor' => 150000]);

        $quote = app(PricingService::class)->quotePlan(
            $plan->fresh(),
            now()->toImmutable()->startOfDay(),
            now()->toImmutable()->addDays(2)->startOfDay(),
            null,
            $corporate->fresh('roomRates'),
        );

        // 2 nights: rack ₹3,600, negotiated ₹1,500 × 2 = ₹3,000 → save ₹600 (not 10%).
        $this->assertSame(360000, $quote['tariff_minor']);
        $this->assertSame(60000, $quote['discount_minor']);
        $this->assertSame(300000, $quote['net_tariff_minor']);
        $this->assertNull($quote['discount']);
        $this->assertSame($corporate->id, $quote['corporate']->id);
    }

    public function test_blanket_discount_applies_when_no_room_rate_and_rack_uses_daily_rates(): void
    {
        [, , $plan] = $this->sellableSetup();
        DailyRate::query()->create([
            'rate_plan_id' => $plan->id,
            'date' => now()->toDateString(),
            'price_minor' => 200000,
        ]);
        $corporate = $this->corporate([
            'discount_type' => Discount::TYPE_PERCENT,
            'discount_value' => 1000,
        ]);

        $quote = app(PricingService::class)->quotePlan(
            $plan->fresh(),
            now()->toImmutable()->startOfDay(),
            now()->toImmutable()->addDay()->startOfDay(),
            null,
            $corporate->fresh('roomRates'),
        );

        // Rack from the daily rate override (₹2,000), blanket 10% off.
        $this->assertSame(200000, $quote['tariff_minor']);
        $this->assertSame(20000, $quote['discount_minor']);
    }

    public function test_negotiated_price_above_rack_charges_normal_rate(): void
    {
        [, , $plan] = $this->sellableSetup(); // ₹1,800/night
        $corporate = $this->corporate();
        $corporate->roomRates()->create(['room_type_id' => $plan->room_type_id, 'price_minor' => 250000]);

        $quote = app(PricingService::class)->quotePlan(
            $plan->fresh(),
            now()->toImmutable()->startOfDay(),
            now()->toImmutable()->addDay()->startOfDay(),
            null,
            $corporate->fresh('roomRates'),
        );

        $this->assertSame(0, $quote['discount_minor']);
        $this->assertSame(180000, $quote['net_tariff_minor']);
    }

    public function test_corporate_rate_suppresses_offers_and_gst_slab_follows_corporate_net(): void
    {
        [$property, , $plan] = $this->sellableSetup(priceMinor: 800000); // ₹8,000 → 18% slab
        Discount::query()->create([
            'property_id' => $property->id, 'code' => null, 'name' => 'Mega Offer',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 5000, 'status' => Discount::STATUS_ACTIVE,
        ]);
        $corporate = $this->corporate();
        $corporate->roomRates()->create(['room_type_id' => $plan->room_type_id, 'price_minor' => 700000]);

        $quote = app(PricingService::class)->quotePlan(
            $plan->fresh(),
            now()->toImmutable()->startOfDay(),
            now()->toImmutable()->addDay()->startOfDay(),
            null,
            $corporate->fresh('roomRates'),
        );

        // The 50% offer is ignored; corporate net ₹7,000/night flips to 5% GST.
        $this->assertSame(100000, $quote['discount_minor']);
        $this->assertSame(700000, $quote['net_tariff_minor']);
        $this->assertSame(500, $quote['gst']['rate_bp']);
    }

    // -------------------------------------------------- public flow

    public function test_company_code_applies_on_search(): void
    {
        [$property, , $plan] = $this->sellableSetup();
        $corporate = $this->corporate();
        $corporate->roomRates()->create(['room_type_id' => $plan->room_type_id, 'price_minor' => 150000]);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString().'&coupon=ACME2026')
            ->assertOk()
            ->assertSee('Corporate rate')
            ->assertSee('Acme Traders')
            ->assertSee('Bill to company')
            ->assertSee('You save');
    }

    public function test_inactive_company_code_is_rejected(): void
    {
        [$property] = $this->sellableSetup();
        $this->corporate(['is_active' => false]);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString().'&coupon=ACME2026')
            ->assertOk()
            ->assertSee('This coupon code is not valid.');
    }

    public function test_corporate_booking_persists_company_and_billing(): void
    {
        [, , $plan] = $this->sellableSetup();
        $corporate = $this->corporate();
        $corporate->roomRates()->create(['room_type_id' => $plan->room_type_id, 'price_minor' => 150000]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, [
                'coupon_code' => 'ACME2026',
                'payment_mode' => 'bill_to_company',
            ]))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        // 2 nights: rack ₹3,600 − negotiated ₹3,000 = ₹600 saving.
        $this->assertSame(360000, $booking->total_amount_minor);
        $this->assertSame(60000, $booking->discount_amount_minor);
        $this->assertSame($corporate->id, $booking->corporate_id);
        $this->assertSame('Acme Traders', $booking->discount_label);
        $this->assertNull($booking->discount_id);
        $this->assertSame(Booking::BILLING_CORPORATE, $booking->billing);
        $this->assertSame(Booking::STATUS_PENDING, $booking->status);
        $this->assertSame(Booking::PAYMENT_UNPAID, $booking->payment_status);
        $this->assertSame(Booking::SOURCE_ONLINE, $booking->source);
    }

    public function test_bill_to_company_requires_a_company_code(): void
    {
        [, , $plan] = $this->sellableSetup();

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, ['payment_mode' => 'bill_to_company']))
            ->assertSessionHasErrors('payment_mode');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_company_code_wins_over_coupon_and_leaves_coupon_usage_untouched(): void
    {
        [$property, , $plan] = $this->sellableSetup();
        $coupon = Discount::query()->create([
            'property_id' => $property->id, 'code' => 'SAVE10', 'name' => 'Welcome Saver',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);
        $corporate = $this->corporate();
        $corporate->roomRates()->create(['room_type_id' => $plan->room_type_id, 'price_minor' => 150000]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, ['coupon_code' => 'ACME2026']))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame($corporate->id, $booking->corporate_id);
        $this->assertNull($booking->discount_id);
        $this->assertSame(0, $coupon->fresh()->times_used);
    }

    public function test_cancelling_a_corporate_booking_touches_no_discount_counters(): void
    {
        [, , $plan] = $this->sellableSetup();
        $corporate = $this->corporate(['discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000]);

        $this->actingAs($this->customer())
            ->post('/book', $this->reservePayload($plan, ['coupon_code' => 'ACME2026']))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), [
            'guest_phone' => '+91 98000 00000',
        ])->assertRedirect();

        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
        $this->assertSame(0, Discount::query()->count()); // nothing to decrement, nothing crashed
    }

    // -------------------------------------------------- fixtures

    private function customer(): User
    {
        return $this->bookingCustomer ??= User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true]);
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

    private function corporate(array $overrides = []): Corporate
    {
        return Corporate::query()->create(array_merge([
            'legal_name' => 'Acme Traders Pvt Ltd',
            'trade_name' => 'Acme Traders',
            'gstin' => '19AABCA1234F1Z5',
            'booking_code' => 'ACME2026',
            'address_line_1' => '1 Industry Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'postal_code' => '700001',
            'country' => 'India',
            'is_active' => true,
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
