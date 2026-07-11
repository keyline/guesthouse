<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DailyRate;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_loads_publicly(): void
    {
        $this->get('/book')->assertOk()->assertSee('Book your stay');
    }

    public function test_search_shows_available_room_types_with_prices(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup(totalRooms: 3);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDays(2)->toDateString())
            ->assertOk()
            ->assertSee($roomType->name)
            ->assertSee($plan->name)
            ->assertSee('3 rooms available');
    }

    public function test_search_hides_room_types_without_online_rooms(): void
    {
        [$property, $roomType] = $this->sellableSetup();

        Room::query()->update(['is_online_bookable' => false]);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertSee('No rooms available online');
    }

    public function test_guest_can_reserve_online(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();

        $response = $this->post('/book', [
            'rate_plan_id' => $plan->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'guest_email' => 'priya@example.com',
            'adults' => 2,
            'children' => 0,
        ]);

        $booking = Booking::query()->firstOrFail();

        $response->assertRedirect(route('book.confirmation', ['bookingNumber' => $booking->booking_number]));

        $this->assertSame(Booking::STATUS_PENDING, $booking->status);
        $this->assertSame(Booking::SOURCE_ONLINE, $booking->source);
        $this->assertNull($booking->room_id);
        $this->assertSame($plan->id, $booking->rate_plan_id);
        $this->assertSame(2 * 180000, $booking->total_amount_minor);

        $this->assertDatabaseHas('room_type_inventory', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'rooms_sold' => 1,
        ]);
    }

    public function test_confirmation_page_shows_booking(): void
    {
        [, , $plan] = $this->sellableSetup();

        $this->post('/book', [
            'rate_plan_id' => $plan->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'adults' => 1,
            'children' => 0,
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->get(route('book.confirmation', ['bookingNumber' => $booking->booking_number]))
            ->assertOk()
            ->assertSee('Reservation received')
            ->assertSee($booking->booking_number);
    }

    public function test_online_channel_respects_allotment(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup(totalRooms: 3, onlineRooms: 1);

        $payload = [
            'rate_plan_id' => $plan->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'First Guest',
            'guest_phone' => '+91 98000 00001',
            'adults' => 1,
            'children' => 0,
        ];

        $this->post('/book', $payload)->assertRedirect();

        $this->post('/book', array_merge($payload, ['guest_name' => 'Second Guest']))
            ->assertSessionHasErrors('rate_plan_id');

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_logged_in_customer_is_linked_to_booking(): void
    {
        [, , $plan] = $this->sellableSetup();
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true]);

        $this->actingAs($customer)->post('/book', [
            'rate_plan_id' => $plan->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => $customer->name,
            'guest_phone' => '+91 98000 00002',
            'adults' => 1,
            'children' => 0,
        ]);

        $this->assertDatabaseHas('bookings', ['user_id' => $customer->id]);
    }

    public function test_admin_can_assign_room_at_check_in(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);

        $this->post('/book', [
            'rate_plan_id' => $plan->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'adults' => 1,
            'children' => 0,
        ]);

        $booking = Booking::query()->firstOrFail();
        $room = Room::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.bookings.assign-room', $booking), ['room_id' => $room->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'room_id' => $room->id]);
    }

    public function test_admin_can_update_rates_from_rate_calendar(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);

        $date = now()->addDay()->toDateString();

        $this->actingAs($admin)
            ->put(route('admin.rate-calendar.update'), [
                'property_id' => $property->id,
                'start' => now()->toDateString(),
                'rates' => [
                    $plan->id => [
                        $date => ['price' => '2750.00'],
                    ],
                ],
                'stop_sell' => [
                    $roomType->id => [
                        $date => '1',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('daily_rates', [
            'rate_plan_id' => $plan->id,
            'price_minor' => 275000,
        ]);

        $this->assertDatabaseHas('room_type_inventory', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'date' => $date.' 00:00:00',
            'stop_sell' => true,
        ]);
    }

    /**
     * @return array{0: Property, 1: RoomType, 2: RatePlan}
     */
    private function sellableSetup(int $totalRooms = 2, ?int $onlineRooms = null): array
    {
        $onlineRooms ??= $totalRooms;

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
            'base_price_minor' => 180000,
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
                'is_online_bookable' => $index <= $onlineRooms,
            ]);
        }

        $plan = RatePlan::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)',
            'code' => 'STD-EP',
            'meal_plan' => RatePlan::MEAL_PLAN_EP,
            'is_refundable' => true,
            'default_price_minor' => 180000,
            'currency' => 'INR',
            'status' => RatePlan::STATUS_ACTIVE,
        ]);

        return [$property, $roomType, $plan];
    }
}
