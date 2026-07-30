<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Corporate;
use App\Models\Discount;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorporateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_companies(): void
    {
        $this->get('/admin/corporates')->assertRedirect('/admin/login');
    }

    public function test_index_create_and_edit_pages_render(): void
    {
        $this->inventory();
        $corporate = $this->corporate();

        $this->actingAs($this->admin())->get(route('admin.corporates.index'))
            ->assertOk()->assertSee('Acme Traders')->assertSee('ACME2026');

        $this->actingAs($this->admin())->get(route('admin.corporates.create'))
            ->assertOk()->assertSee('Company booking code')->assertSee('Negotiated room prices');

        $this->actingAs($this->admin())->get(route('admin.corporates.edit', $corporate))
            ->assertOk()->assertSee('ACME2026');
    }

    public function test_admin_can_create_company_with_rate_card_and_blanket_discount(): void
    {
        [, $roomType] = $this->inventory();

        $this->actingAs($this->admin())
            ->post(route('admin.corporates.store'), $this->companyPayload([
                'booking_code' => 'acme2026',
                'discount_type' => Discount::TYPE_PERCENT,
                'discount_value' => '10',
                'rates' => [$roomType->id => '2000'],
            ]))
            ->assertRedirect();

        $corporate = Corporate::query()->firstOrFail();

        $this->assertSame('ACME2026', $corporate->booking_code);
        $this->assertSame(1000, $corporate->discount_value);
        $this->assertDatabaseHas('corporate_room_rates', [
            'corporate_id' => $corporate->id,
            'room_type_id' => $roomType->id,
            'price_minor' => 200000,
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'subject_type' => 'Corporate',
            'action' => 'created',
        ]);
    }

    public function test_default_billing_saves_and_preselects_bill_to_company_publicly(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.corporates.store'), $this->companyPayload([
                'default_billing' => Booking::BILLING_CORPORATE,
            ]))
            ->assertRedirect();

        $corporate = Corporate::query()->firstOrFail();
        $this->assertSame(Booking::BILLING_CORPORATE, $corporate->default_billing);
        $this->assertTrue($corporate->billsToCompany());

        // On the public booking page the company's code pre-selects "Bill to company".
        $property = Property::query()->create([
            'name' => 'Central Guest House', 'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE, 'city' => 'Kolkata', 'country' => 'India',
            'address' => '12 Guest Road', 'check_in_time_minutes' => 720, 'check_out_time_minutes' => 660,
            'base_price_minor' => 250000, 'currency' => 'INR',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Standard Double', 'code' => 'STD', 'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2, 'max_children' => 1,
        ]);
        Room::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'room_number' => '101', 'status' => Room::STATUS_AVAILABLE, 'is_online_bookable' => true,
        ]);
        RatePlan::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)', 'code' => 'STD-EP', 'meal_plan' => RatePlan::MEAL_PLAN_EP,
            'is_refundable' => true, 'default_price_minor' => 250000, 'currency' => 'INR',
            'status' => RatePlan::STATUS_ACTIVE,
        ]);

        $html = $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString().'&coupon=BHARAT26')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/value="bill_to_company"\s+checked/', $html);
    }

    public function test_blank_rate_removes_the_negotiated_price_on_update(): void
    {
        [, $roomType] = $this->inventory();
        $corporate = $this->corporate();
        $corporate->roomRates()->create(['room_type_id' => $roomType->id, 'price_minor' => 200000]);

        $this->actingAs($this->admin())
            ->put(route('admin.corporates.update', $corporate), $this->companyPayload([
                'rates' => [$roomType->id => ''],
            ]))
            ->assertRedirect();

        $this->assertDatabaseCount('corporate_room_rates', 0);
    }

    public function test_booking_code_cannot_collide_with_a_coupon_code_and_vice_versa(): void
    {
        Discount::query()->create([
            'code' => 'CLASH1', 'name' => 'Coupon',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.corporates.store'), $this->companyPayload(['booking_code' => 'CLASH1']))
            ->assertSessionHasErrors('booking_code');

        $this->corporate(['booking_code' => 'CLASH2']);

        $this->actingAs($this->admin())
            ->post(route('admin.discounts.store'), [
                'apply_mode' => 'coupon',
                'code' => 'CLASH2',
                'name' => 'Colliding Coupon',
                'discount_type' => Discount::TYPE_PERCENT,
                'discount_value' => '10',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_invalid_gstin_is_rejected_and_toggle_works(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.corporates.store'), $this->companyPayload(['gstin' => 'NOT-A-GSTIN']))
            ->assertSessionHasErrors('gstin');

        $corporate = $this->corporate();
        $this->actingAs($this->admin())->post(route('admin.corporates.toggle', $corporate))->assertRedirect();
        $this->assertFalse($corporate->fresh()->is_active);
    }

    public function test_show_page_lists_unbilled_corporate_stays(): void
    {
        [$property, $roomType] = $this->inventory();
        $corporate = $this->corporate();

        Booking::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'status' => Booking::STATUS_CONFIRMED, 'source' => Booking::SOURCE_ONLINE,
            'guest_name' => 'Employee One', 'guest_phone' => '+919800000000',
            'check_in_date' => now()->toDateString(), 'check_out_date' => now()->addDay()->toDateString(),
            'nights' => 1, 'adults' => 1, 'children' => 0,
            'total_amount_minor' => 200000, 'discount_amount_minor' => 20000,
            'tax_rate_bp' => 500, 'tax_amount_minor' => 9000,
            'payment_status' => Booking::PAYMENT_UNPAID, 'currency' => 'INR',
            'corporate_id' => $corporate->id, 'billing' => Booking::BILLING_CORPORATE,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.corporates.show', $corporate))
            ->assertOk()
            ->assertSee('To be billed to company')
            ->assertSee('1,890.00') // ₹2,000 − ₹200 + ₹90 GST
            ->assertSee('Bill to company');
    }

    public function test_admin_booking_with_company_uses_negotiated_rate_and_gst(): void
    {
        [$property, $roomType, $room] = $this->inventory();
        $corporate = $this->corporate();
        $corporate->roomRates()->create(['room_type_id' => $roomType->id, 'price_minor' => 200000]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $room, [
                'total_amount' => null,
                'corporate_id' => $corporate->id,
                'billing' => Booking::BILLING_CORPORATE,
            ]))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        // 2 nights: rack ₹2,500 × 2 = ₹5,000; negotiated ₹2,000 × 2 = ₹4,000.
        $this->assertSame(500000, $booking->total_amount_minor);
        $this->assertSame(100000, $booking->discount_amount_minor);
        $this->assertSame($corporate->id, $booking->corporate_id);
        $this->assertSame('Acme Traders', $booking->discount_label);
        $this->assertSame(Booking::BILLING_CORPORATE, $booking->billing);
        $this->assertSame((int) round(400000 * 0.05), $booking->tax_amount_minor);
    }

    public function test_admin_can_mark_corporate_booking_paid_via_edit(): void
    {
        [$property, $roomType, $room] = $this->inventory();
        $corporate = $this->corporate();

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $room, [
                'corporate_id' => $corporate->id,
                'billing' => Booking::BILLING_CORPORATE,
            ]))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(Booking::PAYMENT_UNPAID, $booking->payment_status);

        $this->actingAs($this->admin())
            ->put(route('admin.bookings.update', $booking), $this->bookingPayload($property, $room, [
                'corporate_id' => $corporate->id,
                'billing' => Booking::BILLING_CORPORATE,
                'payment_status' => Booking::PAYMENT_PAID,
            ]))
            ->assertRedirect();

        $this->assertSame(Booking::PAYMENT_PAID, $booking->fresh()->payment_status);
    }

    public function test_bill_to_company_requires_a_company_on_admin_form(): void
    {
        [$property, , $room] = $this->inventory();

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $room, [
                'billing' => Booking::BILLING_CORPORATE,
            ]))
            ->assertSessionHasErrors('billing');
    }

    // -------------------------------------------------- fixtures

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
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

    private function companyPayload(array $overrides = []): array
    {
        return array_merge([
            'legal_name' => 'Bharat Textiles Ltd',
            'trade_name' => 'Bharat Textiles',
            'gstin' => '19AABCB5678G1Z3',
            'pan' => 'AABCB5678G',
            'booking_code' => 'BHARAT26',
            'discount_type' => '',
            'discount_value' => '',
            'address_line_1' => '5 Mill Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'postal_code' => '700002',
            'country' => 'India',
        ], $overrides);
    }

    private function inventory(): array
    {
        $property = Property::query()->create([
            'name' => 'Central Guest House',
            'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE,
            'city' => 'Kolkata',
            'country' => 'India',
            'address' => '12 Guest Road',
            'check_in_time_minutes' => 720,
            'check_out_time_minutes' => 660,
            'base_price_minor' => 250000,
            'currency' => 'INR',
        ]);

        $roomType = RoomType::query()->create([
            'name' => 'Standard Double',
            'code' => 'STD',
            'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2,
            'max_children' => 1,
        ]);

        $room = Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'status' => Room::STATUS_AVAILABLE,
        ]);

        RatePlan::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)',
            'code' => 'STD-EP',
            'meal_plan' => RatePlan::MEAL_PLAN_EP,
            'is_refundable' => true,
            'default_price_minor' => 250000,
            'currency' => 'INR',
            'status' => RatePlan::STATUS_ACTIVE,
        ]);

        return [$property, $roomType, $room];
    }

    private function bookingPayload(Property $property, Room $room, array $overrides = []): array
    {
        return array_merge([
            'property_id' => $property->id,
            'room_ids' => [$room->id],
            'status' => Booking::STATUS_CONFIRMED,
            'source' => Booking::SOURCE_WALK_IN,
            'guest_name' => 'Employee Guest',
            'guest_email' => 'employee@example.com',
            'guest_phone' => '+91 90000 00000',
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-12',
            'adults' => 2,
            'children' => 0,
            'total_amount' => null,
            'currency' => 'INR',
            'special_requests' => null,
            'internal_notes' => null,
        ], $overrides);
    }
}
