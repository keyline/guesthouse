<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
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
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('room_types', [
            'name' => 'Deluxe Double',
            'code' => 'DLX',
        ]);
    }

    public function test_admin_can_create_room(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();

        $this->actingAs($admin)
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
