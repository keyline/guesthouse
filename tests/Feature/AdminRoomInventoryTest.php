<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Support\AdminPropertyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoomInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_rooms(): void
    {
        $this->get('/admin/rooms')->assertRedirect('/admin/login');
    }

    public function test_customer_cannot_access_rooms(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($customer)->get('/admin/rooms')->assertForbidden();
    }

    public function test_admin_can_create_room_type(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.room-types.store'), $this->roomTypePayload([
                'name' => 'Deluxe Double',
                'code' => 'DLX',
                'is_pet_friendly' => '1',
                'extra_bed_available' => '1',
                'max_extra_beds' => 1,
                'extra_bed_charge' => '750.50',
                'extra_bed_charge_basis' => 'per_night',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('room_types', [
            'name' => 'Deluxe Double',
            'code' => 'deluxe-double',
            'is_pet_friendly' => true,
            'extra_bed_available' => true,
            'max_extra_beds' => 1,
            'extra_bed_charge_minor' => 75050,
            'extra_bed_charge_basis' => 'per_night',
        ]);
    }

    public function test_room_type_code_is_slugged_and_numbered_when_duplicate(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.room-types.store'), $this->roomTypePayload(['name' => 'Basic Testing']))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.room-types.store'), $this->roomTypePayload(['name' => 'Basic Testing']))->assertRedirect();

        $this->assertDatabaseHas('room_types', ['name' => 'Basic Testing', 'code' => 'basic-testing']);
        $this->assertDatabaseHas('room_types', ['name' => 'Basic Testing', 'code' => 'basic-testing-2']);
    }

    public function test_admin_can_create_room(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();

        $this->actingAs($admin)
            ->withSession([AdminPropertyScope::SESSION_KEY => $property->id])
            ->post(route('admin.rooms.store'), $this->roomPayload($property, $roomType, [
                'room_number' => '101',
                'floor' => '1st Floor',
                'is_accessible' => '1',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => '1st Floor',
            'status' => Room::STATUS_AVAILABLE,
            'is_accessible' => true,
        ]);
    }

    public function test_add_room_uses_header_property_and_has_no_property_dropdown(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();

        $this->actingAs($admin)
            ->withSession([AdminPropertyScope::SESSION_KEY => $property->id])
            ->get(route('admin.rooms.create', ['property_id' => 999999]))
            ->assertOk()
            ->assertSee($property->name)
            ->assertDontSee('id="property_id"', false)
            ->assertSee('name="property_id" value="'.$property->id.'"', false);
    }

    public function test_admin_can_update_room_status(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();
        $room = Room::query()->create($this->roomPayload($property, $roomType, ['room_number' => '201']));

        $this->actingAs($admin)
            ->put(route('admin.rooms.update', $room), $this->roomPayload($property, $roomType, [
                'room_number' => '201',
                'status' => Room::STATUS_MAINTENANCE,
                'notes' => 'Deep cleaning scheduled.',
            ]))
            ->assertRedirect(route('admin.rooms.edit', $room));

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => Room::STATUS_MAINTENANCE,
            'notes' => 'Deep cleaning scheduled.',
        ]);
    }

    public function test_room_profile_page_redirects_to_edit(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();
        $room = Room::query()->create($this->roomPayload($property, $roomType, [
            'room_number' => '303',
        ]));

        $this->actingAs($admin)
            ->get(route('admin.rooms.show', $room))
            ->assertRedirect(route('admin.rooms.edit', $room));
    }

    public function test_room_type_with_rooms_cannot_be_deleted(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();

        Room::query()->create($this->roomPayload($property, $roomType));

        $this->actingAs($admin)
            ->delete(route('admin.room-types.destroy', $roomType))
            ->assertSessionHasErrors('room_type');

        $this->assertDatabaseHas('room_types', [
            'id' => $roomType->id,
        ]);
    }

    public function test_room_type_with_only_historical_booking_can_be_deactivated_without_losing_history(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $type = $this->roomType();
        $room = Room::query()->create($this->roomPayload($property, $type, ['room_number' => '404', 'is_online_bookable' => true]));
        $booking = Booking::query()->create([
            'property_id' => $property->id, 'room_type_id' => $type->id, 'room_id' => $room->id,
            'status' => Booking::STATUS_CHECKED_OUT, 'source' => Booking::SOURCE_DIRECT, 'guest_name' => 'Past Guest',
            'check_in_date' => now()->subDays(3), 'check_out_date' => now()->subDays(2), 'nights' => 1,
            'adults' => 1, 'children' => 0, 'total_amount_minor' => 100000, 'currency' => 'INR',
        ]);

        $this->actingAs($admin)->post(route('admin.room-types.toggle-status', $type))->assertRedirect();

        $this->assertDatabaseHas('room_types', ['id' => $type->id, 'status' => RoomType::STATUS_INACTIVE]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'is_online_bookable' => false]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'room_type_id' => $type->id]);
        $this->actingAs($admin)->get(route('admin.rooms.index'))->assertDontSee('404');
    }

    public function test_room_type_cannot_be_deactivated_with_upcoming_booking(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $type = $this->roomType();
        $room = Room::query()->create($this->roomPayload($property, $type));
        Booking::query()->create([
            'property_id' => $property->id, 'room_type_id' => $type->id, 'room_id' => $room->id,
            'status' => Booking::STATUS_CONFIRMED, 'source' => Booking::SOURCE_DIRECT, 'guest_name' => 'Future Guest',
            'check_in_date' => now()->addDay(), 'check_out_date' => now()->addDays(2), 'nights' => 1,
            'adults' => 1, 'children' => 0, 'total_amount_minor' => 100000, 'currency' => 'INR',
        ]);

        $this->actingAs($admin)->post(route('admin.room-types.toggle-status', $type))->assertSessionHasErrors('room_type');
        $this->assertDatabaseHas('room_types', ['id' => $type->id, 'status' => RoomType::STATUS_ACTIVE]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    private function property(): Property
    {
        return Property::query()->create([
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
            'base_price_minor' => 250000,
            'currency' => 'INR',
            'sort_order' => 0,
        ]);
    }

    private function roomType(): RoomType
    {
        return RoomType::query()->create([
            'name' => 'Standard Double',
            'code' => 'STD',
            'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2,
            'max_children' => 1,
            'sort_order' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function roomTypePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Standard Double',
            'code' => 'STD',
            'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2,
            'max_children' => 1,
            'sort_order' => 0,
            'description' => 'Standard room for two guests.',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function roomPayload(Property $property, RoomType $roomType, array $overrides = []): array
    {
        return array_merge([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => '1st Floor',
            'status' => Room::STATUS_AVAILABLE,
            'is_smoking' => '0',
            'is_accessible' => '0',
            'notes' => null,
        ], $overrides);
    }
}
