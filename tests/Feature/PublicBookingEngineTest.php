<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Amenity;
use App\Models\DailyRate;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\RoomImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingEngineTest extends TestCase
{
    use RefreshDatabase;

    private ?User $bookingCustomer = null;

    private function customer(): User
    {
        return $this->bookingCustomer ??= User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true]);
    }

    public function test_search_page_loads_publicly(): void
    {
        $this->get('/book')->assertOk()->assertSee('Book your stay');
    }

    public function test_landing_widget_submits_to_the_booking_engine(): void
    {
        [$property] = $this->sellableSetup();

        $this->get('/')
            ->assertOk()
            ->assertSee('action="'.route('book.search').'"', false)
            ->assertSee('name="property_id"', false)
            ->assertSee('<option value="'.$property->id.'"', false)
            ->assertSee('name="check_in"', false)
            ->assertSee('name="check_out"', false)
            ->assertSee('name="guest_count"', false)
            ->assertSee('name="event_type"', false)
            ->assertSee('Enter number of guests')
            ->assertSee('Marriage')
            ->assertSee('Anniversary')
            ->assertSee('Birthday')
            ->assertSee('Corporate Party')
            ->assertSee('Meeting')
            ->assertSee('Others')
            ->assertSee('Check Availability');
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

    public function test_search_shows_guest_visible_property_facilities_above_rooms(): void
    {
        [$property] = $this->sellableSetup();
        $parking = Amenity::query()->where('name', 'Parking')->firstOrFail();
        $property->amenities()->attach($parking->id);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertSee('Property facilities')
            ->assertSee('Included at '.$property->name)
            ->assertSee('Parking');
    }

    public function test_search_keeps_offline_room_types_visible_as_unavailable(): void
    {
        [$property, $roomType] = $this->sellableSetup();

        Room::query()->update(['is_online_bookable' => false]);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertSee($roomType->name)
            ->assertSee('Not available online')
            ->assertDontSee('class="btn-reserve" data-panel-reserve', false);
    }

    public function test_category_without_active_rate_plan_remains_visible(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();
        $plan->update(['status' => RatePlan::STATUS_INACTIVE]);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString())
            ->assertOk()->assertSee($roomType->name)->assertSee('Rates not configured')->assertSee('Online pricing is not configured');
    }

    public function test_sold_out_room_type_remains_visible_without_reserve_button(): void
    {
        [$property, $roomType] = $this->sellableSetup(totalRooms: 1, onlineRooms: 1);
        Booking::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'status' => Booking::STATUS_CONFIRMED, 'source' => Booking::SOURCE_ONLINE,
            'guest_name' => 'Sold Guest', 'check_in_date' => now(), 'check_out_date' => now()->addDay(),
            'nights' => 1, 'adults' => 1, 'children' => 0, 'total_amount_minor' => 180000, 'currency' => 'INR',
        ]);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString())
            ->assertOk()->assertSee($roomType->name)->assertSee('Sold out for these dates')->assertSee('room-panel--sold', false)->assertDontSee('class="btn-reserve" data-panel-reserve', false);
    }

    public function test_room_gallery_uses_photos_from_physical_rooms(): void
    {
        [$property] = $this->sellableSetup();
        $room = Room::query()->firstOrFail();
        RoomImage::query()->create(['room_id' => $room->id, 'path' => 'rooms/physical-room.jpg', 'alt_text' => 'Physical room photo']);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString())
            ->assertOk()->assertSee('storage/rooms/physical-room.jpg')->assertSee('Physical room photo');
    }

    public function test_guest_can_reserve_online(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();

        $response = $this->actingAs($this->customer())->post('/book', [
            'rooms' => [$plan->id => 1],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'guest_email' => 'priya@example.com',
            'adults' => 2,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
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

    public function test_guest_can_reserve_multiple_rooms_in_one_booking(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup(totalRooms: 3);

        $response = $this->actingAs($this->customer())->post('/book', [
            'rooms' => [$plan->id => 2],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'adults' => 4,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
        ]);

        $bookings = Booking::query()->orderBy('id')->get();
        $this->assertCount(2, $bookings);
        $this->assertNotNull($bookings->first()->booking_group_code);
        $this->assertSame($bookings->first()->booking_group_code, $bookings->last()->booking_group_code);
        // Each room is priced individually at the plan quote.
        $this->assertSame([180000, 180000], $bookings->pluck('total_amount_minor')->all());

        $response->assertRedirect(route('book.confirmation', ['bookingNumber' => $bookings->first()->booking_number]));

        $this->get(route('book.confirmation', ['bookingNumber' => $bookings->first()->booking_number]))
            ->assertOk()
            ->assertSee('2 rooms reserved together');

        $this->assertDatabaseHas('room_type_inventory', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'rooms_sold' => 2,
        ]);
    }

    public function test_reservation_rejected_when_more_rooms_requested_than_available(): void
    {
        [, , $plan] = $this->sellableSetup(totalRooms: 3, onlineRooms: 1);

        $this->actingAs($this->customer())->post('/book', [
            'rooms' => [$plan->id => 2],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'adults' => 4,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
        ])->assertSessionHasErrors('rooms');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_confirmation_page_shows_booking(): void
    {
        [, , $plan] = $this->sellableSetup();

        $this->actingAs($this->customer())->post('/book', [
            'rooms' => [$plan->id => 1],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'adults' => 1,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
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
            'rooms' => [$plan->id => 1],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'First Guest',
            'guest_phone' => '+91 98000 00001',
            'adults' => 1,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
        ];

        $this->actingAs($this->customer())->post('/book', $payload)->assertRedirect();

        $this->actingAs($this->customer())->post('/book', array_merge($payload, ['guest_name' => 'Second Guest']))
            ->assertSessionHasErrors('rooms');

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_logged_in_customer_is_linked_to_booking(): void
    {
        [, , $plan] = $this->sellableSetup();
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true]);

        $this->actingAs($customer)->post('/book', [
            'rooms' => [$plan->id => 1],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => $customer->name,
            'guest_phone' => '+91 98000 00002',
            'adults' => 1,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
        ]);

        $this->assertDatabaseHas('bookings', ['user_id' => $customer->id]);
    }

    public function test_admin_can_assign_room_at_check_in(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);

        $this->actingAs($this->customer())->post('/book', [
            'rooms' => [$plan->id => 1],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'adults' => 1,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
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
