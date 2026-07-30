<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Support\AdminPropertyScope;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_bookings(): void
    {
        $this->get('/admin/bookings')->assertRedirect('/admin/login');
    }

    public function test_customer_cannot_access_bookings(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($customer)->get('/admin/bookings')->assertForbidden();
    }

    public function test_booking_report_shows_created_date_and_payment_details(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();
        $booking = Booking::query()->create($this->bookingModelPayload($property, $roomType, $room, [
            'payment_status' => Booking::PAYMENT_PAID,
            'tax_amount_minor' => 7000,
        ]));
        $booking->forceFill(['created_at' => '2026-07-12 10:30:00'])->saveQuietly();
        Payment::query()->create([
            'booking_id' => $booking->id,
            'gateway' => 'manual',
            'method' => 'upi',
            'status' => Payment::STATUS_CAPTURED,
            'amount_minor' => $booking->grossTotalMinor(),
            'currency' => 'INR',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('12 Jul 2026')
            ->assertSee('UPI')
            ->assertSee('GST included')
            ->assertSee('paid');
    }

    public function test_admin_can_create_booking(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, $room, [
                'guest_name' => 'Riya Sen',
                'check_in_date' => '2026-08-10',
                'check_out_date' => '2026-08-12',
                'total_amount' => '5600.00',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'guest_name' => 'Riya Sen',
            'nights' => 2,
            'total_amount_minor' => 560000,
            'status' => Booking::STATUS_CONFIRMED,
        ]);
    }

    public function test_walk_in_can_book_multiple_rooms_in_one_linked_group(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $firstRoom] = $this->inventory();
        $secondRoom = Room::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'room_number' => '102', 'status' => Room::STATUS_AVAILABLE,
        ]);

        $payload = $this->bookingPayload($property, $roomType, $firstRoom, [
            'room_ids' => [$firstRoom->id, $secondRoom->id],
            'source' => Booking::SOURCE_WALK_IN,
        ]);
        $this->actingAs($admin)->post(route('admin.bookings.store'), $payload)->assertRedirect();

        $bookings = Booking::query()->orderBy('id')->get();
        $this->assertCount(2, $bookings);
        $this->assertNotNull($bookings->first()->booking_group_code);
        $this->assertSame($bookings->first()->booking_group_code, $bookings->last()->booking_group_code);
        $this->assertEqualsCanonicalizing([$firstRoom->id, $secondRoom->id], $bookings->pluck('room_id')->all());
        $this->assertSame([140000, 140000], $bookings->pluck('total_amount_minor')->all());
        $this->assertSame(280000, $bookings->sum('total_amount_minor'));
    }

    public function test_new_booking_cannot_be_created_as_checked_in_or_checked_out(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();

        foreach ([Booking::STATUS_CHECKED_IN, Booking::STATUS_CHECKED_OUT, Booking::STATUS_CANCELLED] as $status) {
            $this->actingAs($admin)
                ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, $room, ['status' => $status]))
                ->assertSessionHasErrors('status');
        }

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_booking_cannot_be_edited_into_checked_in_status(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();
        $booking = Booking::query()->create($this->bookingModelPayload($property, $roomType, $room));

        $this->actingAs($admin)
            ->put(route('admin.bookings.update', $booking), $this->bookingPayload($property, $roomType, $room, [
                'status' => Booking::STATUS_CHECKED_IN,
            ]))
            ->assertSessionHasErrors('status');

        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->fresh()->status);
    }

    public function test_typed_in_guest_gets_a_reusable_profile_that_is_matched_on_the_next_booking(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, $room, [
                'guest_name' => 'Walk-in Guest', 'guest_email' => null, 'guest_phone' => '98700 12345',
            ]))
            ->assertRedirect();

        $profile = User::query()->where('role', User::ROLE_CUSTOMER)->where('phone_e164', '+919870012345')->first();
        $this->assertNotNull($profile);
        $this->assertNull($profile->email);
        $this->assertSame($profile->id, Booking::query()->firstOrFail()->user_id);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, $room, [
                'guest_name' => 'Walk-in Guest', 'guest_email' => null, 'guest_phone' => '+91 98700 12345',
                'check_in_date' => '2026-09-01', 'check_out_date' => '2026-09-02',
            ]))
            ->assertRedirect();

        $this->assertSame(1, User::query()->where('role', User::ROLE_CUSTOMER)->count());
        $this->assertSame([$profile->id], Booking::query()->pluck('user_id')->unique()->all());
    }

    public function test_overlapping_booking_for_same_room_is_rejected(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();

        Booking::query()->create($this->bookingModelPayload($property, $roomType, $room, [
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-12',
        ]));

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, $room, [
                'check_in_date' => '2026-08-11',
                'check_out_date' => '2026-08-13',
            ]))
            ->assertSessionHasErrors('room_ids');

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_cancelled_booking_releases_availability(): void
    {
        [$property, $roomType, $room] = $this->inventory();

        $booking = Booking::query()->create($this->bookingModelPayload($property, $roomType, $room, [
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-12',
        ]));

        $available = app(AvailabilityService::class)->roomIsAvailable(
            $room,
            CarbonImmutable::parse('2026-08-10'),
            CarbonImmutable::parse('2026-08-12'),
            $booking->id,
        );

        $this->assertTrue($available);
    }

    public function test_availability_calendar_loads_for_admin(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();

        Booking::query()->create($this->bookingModelPayload($property, $roomType, $room, [
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-12',
        ]));

        $this->actingAs($admin)
            ->get(route('admin.availability.index', ['start' => '2026-08-10']))
            ->assertOk()
            ->assertSee('14-Day Availability')
            ->assertSee('Standard Double')
            ->assertSee('0 booked');
    }

    public function test_admin_can_cancel_booking(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();

        $booking = Booking::query()->create($this->bookingModelPayload($property, $roomType, $room));

        $this->actingAs($admin)
            ->delete(route('admin.bookings.destroy', $booking), ['cancellation_reason' => 'guest_request'])
            ->assertRedirect(route('admin.bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_CANCELLED,
        ]);
    }

    public function test_price_fills_automatically_from_default_rate_plan_when_total_left_blank(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $firstRoom] = $this->inventory();
        $secondRoom = Room::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'room_number' => '102', 'status' => Room::STATUS_AVAILABLE,
        ]);
        RatePlan::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'name' => 'Standard Rate', 'code' => 'STD-EP', 'meal_plan' => 'ep',
            'default_price_minor' => 300000, 'currency' => 'INR', 'status' => RatePlan::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, $firstRoom, [
                'room_ids' => [$firstRoom->id, $secondRoom->id],
                'total_amount' => null,
                'check_in_date' => '2026-08-10',
                'check_out_date' => '2026-08-12',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // Two nights at the default plan rate, priced per room (not split).
        $this->assertSame([600000, 600000], Booking::query()->orderBy('id')->pluck('total_amount_minor')->all());
    }

    public function test_booking_form_only_lists_rate_plans_for_selected_property(): void
    {
        $admin = $this->adminUser();
        [$north, $roomType] = $this->inventory();
        $south = Property::query()->create([
            'name' => 'South Hotel', 'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE, 'city' => 'Kolkata', 'country' => 'India',
            'address' => '22 South Road', 'base_price_minor' => 200000, 'currency' => 'INR',
        ]);
        Room::query()->create(['property_id' => $south->id, 'room_type_id' => $roomType->id, 'room_number' => 'S1', 'status' => Room::STATUS_AVAILABLE]);
        RatePlan::query()->create(['property_id' => $north->id, 'room_type_id' => $roomType->id, 'name' => 'North Exclusive Rate', 'code' => 'NORTH-EP', 'meal_plan' => 'ep', 'default_price_minor' => 280000, 'currency' => 'INR', 'status' => RatePlan::STATUS_ACTIVE]);
        RatePlan::query()->create(['property_id' => $south->id, 'room_type_id' => $roomType->id, 'name' => 'South Secret Rate', 'code' => 'SOUTH-EP', 'meal_plan' => 'ep', 'default_price_minor' => 220000, 'currency' => 'INR', 'status' => RatePlan::STATUS_ACTIVE]);

        $this->actingAs($admin)
            ->withSession([AdminPropertyScope::SESSION_KEY => $north->id])
            ->get(route('admin.bookings.create'))
            ->assertOk()
            ->assertSee('₹2,800.00/night')
            ->assertDontSee('₹2,200.00/night');
    }

    public function test_edit_form_shows_room_board_with_current_room_selectable(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();
        $booking = Booking::query()->create($this->bookingModelPayload($property, $roomType, $room));

        // The booking's own room must not be blocked by its own dates.
        $this->actingAs($admin)
            ->withSession([AdminPropertyScope::SESSION_KEY => $property->id])
            ->get(route('admin.bookings.edit', $booking))
            ->assertOk()
            ->assertSee('data-room-card="'.$room->id.'"', false)
            ->assertSee('data-available="1"', false)
            ->assertSee('data-room-grouping="floor"', false)
            ->assertSee('data-room-grouping="category"', false)
            ->assertSee('data-category="'.$roomType->name.'"', false)
            ->assertSee('room-card-floor', false)
            ->assertSee($room->floor);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: Property, 1: RoomType, 2: Room}
     */
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
            'property_id' => $property->id,
            'name' => 'Standard Double',
            'code' => 'STD',
            'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2,
            'max_children' => 1,
            'base_price_minor' => 280000,
            'currency' => 'INR',
        ]);

        $room = Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'status' => Room::STATUS_AVAILABLE,
        ]);

        return [$property, $roomType, $room];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bookingPayload(Property $property, RoomType $roomType, Room $room, array $overrides = []): array
    {
        return array_merge([
            'property_id' => $property->id,
            'room_ids' => [$room->id],
            'status' => Booking::STATUS_CONFIRMED,
            'source' => Booking::SOURCE_DIRECT,
            'guest_name' => 'Guest User',
            'guest_email' => 'guest@example.com',
            'guest_phone' => '+91 90000 00000',
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-11',
            'adults' => 2,
            'children' => 0,
            'total_amount' => '2800.00',
            'currency' => 'INR',
            'special_requests' => null,
            'internal_notes' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bookingModelPayload(Property $property, RoomType $roomType, Room $room, array $overrides = []): array
    {
        return array_merge([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'status' => Booking::STATUS_CONFIRMED,
            'source' => Booking::SOURCE_DIRECT,
            'guest_name' => 'Guest User',
            'guest_email' => 'guest@example.com',
            'guest_phone' => '+91 90000 00000',
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-11',
            'nights' => 1,
            'adults' => 2,
            'children' => 0,
            'total_amount_minor' => 280000,
            'currency' => 'INR',
        ], $overrides);
    }
}
