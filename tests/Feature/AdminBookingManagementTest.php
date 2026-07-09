<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
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
            ->assertSessionHasErrors('room_id');

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
            ->delete(route('admin.bookings.destroy', $booking))
            ->assertRedirect(route('admin.bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_CANCELLED,
        ]);
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
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
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
