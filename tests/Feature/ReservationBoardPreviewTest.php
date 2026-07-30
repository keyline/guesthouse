<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationBoardPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_isolated_reservation_board_preview(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reservation-board-preview'))
            ->assertOk()
            ->assertSee('Reservation Board')
            ->assertSee('No rooms available');
    }

    public function test_booking_chip_carries_modal_details_instead_of_navigating_away(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $property = Property::query()->create([
            'name' => 'Central Guest House', 'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE, 'city' => 'Kolkata', 'country' => 'India',
            'address' => '12 Guest Road', 'base_price_minor' => 250000, 'currency' => 'INR',
        ]);
        $roomType = RoomType::query()->create([
            'property_id' => $property->id, 'name' => 'Standard Double', 'code' => 'STD',
            'status' => RoomType::STATUS_ACTIVE, 'max_adults' => 2, 'max_children' => 1,
            'base_price_minor' => 280000, 'currency' => 'INR',
        ]);
        $room = Room::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'room_number' => '101', 'status' => Room::STATUS_AVAILABLE,
        ]);
        $booking = Booking::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id, 'room_id' => $room->id,
            'status' => Booking::STATUS_CONFIRMED, 'source' => Booking::SOURCE_WALK_IN,
            'guest_name' => 'Riya Sen', 'guest_phone' => '+919870012345',
            'check_in_date' => now()->toDateString(), 'check_out_date' => now()->addDay()->toDateString(),
            'nights' => 1, 'adults' => 2, 'children' => 0, 'total_amount_minor' => 280000, 'currency' => 'INR',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reservation-board-preview'))
            ->assertOk()
            ->assertSee('Riya Sen')
            ->assertSee('data-booking', false)
            ->assertSee('bookingModal', false)
            ->assertSee(e(route('admin.bookings.stay', $booking)), false);

        // The chip itself must be a button, not a link that leaves the board.
        $this->assertStringNotContainsString(
            '<a href="'.e(route('admin.bookings.stay', $booking)).'" class="rb-booking"',
            $response->getContent(),
        );
    }
}
