<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDiscountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_discounts(): void
    {
        $this->get('/admin/discounts')->assertRedirect('/admin/login');
    }

    public function test_index_lists_coupons_and_offers(): void
    {
        $property = $this->property();
        Discount::query()->create([
            'property_id' => $property->id, 'code' => 'WELCOME10', 'name' => 'Welcome Saver',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);
        Discount::query()->create([
            'property_id' => null, 'code' => null, 'name' => 'Long Stay Deal',
            'discount_type' => Discount::TYPE_FIXED, 'discount_value' => 50000, 'min_nights' => 3, 'status' => Discount::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.discounts.index'))
            ->assertOk()
            ->assertSee('WELCOME10')
            ->assertSee('Long Stay Deal')
            ->assertSee('Automatic');
    }

    public function test_create_and_edit_pages_render(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.discounts.create'))
            ->assertOk()
            ->assertSee('How does it apply?')
            ->assertSee('Coupon code');

        $discount = Discount::query()->create([
            'code' => 'EDITME', 'name' => 'Editable',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.discounts.edit', $discount))
            ->assertOk()
            ->assertSee('EDITME');
    }

    public function test_admin_can_scope_an_offer_to_a_room_category(): void
    {
        $property = $this->property();
        $roomType = RoomType::query()->create([
            'name' => 'Deluxe Double', 'code' => 'DLX-SCOPE',
            'status' => RoomType::STATUS_ACTIVE, 'max_adults' => 2, 'max_children' => 1, 'sort_order' => 0,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.discounts.store'), [
                'apply_mode' => 'automatic',
                'name' => 'Deluxe Days',
                'property_id' => $property->id,
                'room_type_id' => $roomType->id,
                'discount_type' => Discount::TYPE_PERCENT,
                'discount_value' => '15',
            ])
            ->assertRedirect(route('admin.discounts.index'));

        $this->assertDatabaseHas('discounts', [
            'name' => 'Deluxe Days',
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
        ]);

        // The scope shows on the list so staff can tell offers apart.
        $this->actingAs($this->admin())
            ->get(route('admin.discounts.index'))
            ->assertOk()
            ->assertSee('Deluxe Double');
    }

    public function test_admin_can_create_a_coupon_with_rupee_and_percent_conversion(): void
    {
        $property = $this->property();

        $this->actingAs($this->admin())
            ->post(route('admin.discounts.store'), [
                'apply_mode' => 'coupon',
                'code' => 'welcome10',
                'name' => 'Welcome Saver',
                'property_id' => $property->id,
                'discount_type' => Discount::TYPE_PERCENT,
                'discount_value' => '12.5',
                'max_discount' => '500',
                'min_nights' => 2,
                'min_amount' => '2000',
                'max_uses' => 10,
            ])
            ->assertRedirect(route('admin.discounts.index'));

        $this->assertDatabaseHas('discounts', [
            'code' => 'WELCOME10',
            'discount_value' => 1250,
            'max_discount_minor' => 50000,
            'min_amount_minor' => 200000,
            'min_nights' => 2,
            'max_uses' => 10,
            'status' => Discount::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('admin_activity_logs', [
            'subject_type' => 'Discount',
            'action' => 'created',
        ]);
    }

    public function test_automatic_offer_needs_no_code_and_percent_over_100_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.discounts.store'), [
                'apply_mode' => 'automatic',
                'name' => 'Everything Free',
                'discount_type' => Discount::TYPE_PERCENT,
                'discount_value' => '150',
            ])
            ->assertSessionHasErrors('discount_value');

        $this->actingAs($this->admin())
            ->post(route('admin.discounts.store'), [
                'apply_mode' => 'automatic',
                'name' => 'Long Stay Deal',
                'discount_type' => Discount::TYPE_PERCENT,
                'discount_value' => '15',
                'min_nights' => 3,
            ])
            ->assertRedirect(route('admin.discounts.index'));

        $offer = Discount::query()->firstOrFail();
        $this->assertNull($offer->code);
        $this->assertSame(1500, $offer->discount_value);
    }

    public function test_toggle_and_update(): void
    {
        $discount = Discount::query()->create([
            'code' => 'PAUSE10', 'name' => 'Pausable',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.discounts.toggle', $discount))
            ->assertRedirect();
        $this->assertSame(Discount::STATUS_INACTIVE, $discount->fresh()->status);

        $this->actingAs($this->admin())
            ->put(route('admin.discounts.update', $discount), [
                'apply_mode' => 'coupon',
                'code' => 'PAUSE10',
                'name' => 'Renamed Saver',
                'discount_type' => Discount::TYPE_FIXED,
                'discount_value' => '250',
            ])
            ->assertRedirect(route('admin.discounts.index'));

        $this->assertSame('Renamed Saver', $discount->fresh()->name);
        $this->assertSame(25000, $discount->fresh()->discount_value);
    }

    public function test_manager_cannot_touch_another_propertys_discount(): void
    {
        $property = $this->property();
        $otherProperty = $this->property('Other House');
        $discount = Discount::query()->create([
            'property_id' => $otherProperty->id, 'code' => 'THEIRS', 'name' => 'Their Coupon',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);

        $manager = User::factory()->create(['role' => User::ROLE_PROPERTY_MANAGER, 'is_active' => true]);
        $manager->managedProperties()->attach($property->id);

        $this->actingAs($manager)->get(route('admin.discounts.edit', $discount))->assertNotFound();
        $this->actingAs($manager)->post(route('admin.discounts.toggle', $discount))->assertNotFound();
    }

    public function test_admin_auto_quoted_booking_applies_offer_and_gst(): void
    {
        [$property, $roomType, $room] = $this->inventory();
        $offer = Discount::query()->create([
            'property_id' => $property->id, 'code' => null, 'name' => 'Walk-in Deal',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $room, ['total_amount' => null]))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        // ₹2,500/night × 2 nights = ₹5,000 tariff, 10% offer, GST 5% on ₹4,500.
        $this->assertSame(500000, $booking->total_amount_minor);
        $this->assertSame(50000, $booking->discount_amount_minor);
        $this->assertSame($offer->id, $booking->discount_id);
        $this->assertSame(500, $booking->tax_rate_bp);
        $this->assertSame((int) round(450000 * 0.05), $booking->tax_amount_minor);
        $this->assertSame(1, $offer->fresh()->times_used);
    }

    public function test_admin_manual_total_gets_gst_but_no_discount(): void
    {
        [$property, , $room] = $this->inventory();
        Discount::query()->create([
            'property_id' => $property->id, 'code' => null, 'name' => 'Walk-in Deal',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $room, ['total_amount' => '4000.00']))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        $this->assertSame(400000, $booking->total_amount_minor);
        $this->assertSame(0, $booking->discount_amount_minor);
        $this->assertNull($booking->discount_id);
        $this->assertSame((int) round(400000 * 0.05), $booking->tax_amount_minor);
    }

    public function test_admin_cancellation_releases_the_offer_use(): void
    {
        [$property, , $room] = $this->inventory();
        $offer = Discount::query()->create([
            'property_id' => $property->id, 'code' => null, 'name' => 'Walk-in Deal',
            'discount_type' => Discount::TYPE_PERCENT, 'discount_value' => 1000, 'status' => Discount::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $room, ['total_amount' => null]))
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(1, $offer->fresh()->times_used);

        $this->actingAs($this->admin())
            ->delete(route('admin.bookings.destroy', $booking), ['cancellation_reason' => 'guest_request'])
            ->assertRedirect();

        $this->assertSame(0, $offer->fresh()->times_used);
    }

    // -------------------------------------------------- fixtures

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
    }

    private function property(string $name = 'Central Guest House'): Property
    {
        return Property::query()->create([
            'name' => $name,
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
    }

    private function inventory(): array
    {
        $property = $this->property();

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
            'guest_name' => 'Guest User',
            'guest_email' => 'guest@example.com',
            'guest_phone' => '+91 90000 00000',
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-12',
            'adults' => 2,
            'children' => 0,
            'total_amount' => '2800.00',
            'currency' => 'INR',
            'special_requests' => null,
            'internal_notes' => null,
        ], $overrides);
    }
}
