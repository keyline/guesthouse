<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOnlineInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_online_inventory(): void
    {
        $this->get('/admin/online-inventory')->assertRedirect('/admin/login');
    }

    public function test_customer_cannot_access_online_inventory(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($customer)->get('/admin/online-inventory')->assertForbidden();
    }

    public function test_super_admin_sees_property_overview_when_no_property_selected(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();

        $this->actingAs($admin)
            ->get(route('admin.online-inventory.index'))
            ->assertOk()
            ->assertSee('Choose a property')
            ->assertSee($property->name);
    }

    public function test_admin_sees_rooms_grouped_for_selected_property(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();
        $this->room($property, $roomType, ['room_number' => '101']);
        $this->room($property, $roomType, ['room_number' => '102', 'status' => Room::STATUS_MAINTENANCE]);

        $this->actingAs($admin)
            ->get(route('admin.online-inventory.index', ['property_id' => $property->id]))
            ->assertOk()
            ->assertSee($property->name)
            ->assertSee('101')
            ->assertSee('102')
            ->assertSee('Save changes');
    }

    public function test_admin_can_mark_rooms_online_bookable(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();
        $roomOne = $this->room($property, $roomType, ['room_number' => '101']);
        $roomTwo = $this->room($property, $roomType, ['room_number' => '102', 'is_online_bookable' => true]);

        $this->actingAs($admin)
            ->put(route('admin.online-inventory.update'), [
                'property_id' => $property->id,
                'room_ids' => [$roomOne->id],
            ])
            ->assertRedirect(route('admin.online-inventory.index', ['property_id' => $property->id]));

        $this->assertDatabaseHas('rooms', ['id' => $roomOne->id, 'is_online_bookable' => true]);
        $this->assertDatabaseHas('rooms', ['id' => $roomTwo->id, 'is_online_bookable' => false]);
    }

    public function test_admin_can_clear_all_online_rooms(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();
        $room = $this->room($property, $roomType, ['is_online_bookable' => true]);

        $this->actingAs($admin)
            ->put(route('admin.online-inventory.update'), [
                'property_id' => $property->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'is_online_bookable' => false]);
    }

    public function test_property_manager_cannot_update_unassigned_property(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);
        $property = $this->property();
        $roomType = $this->roomType();
        $room = $this->room($property, $roomType);

        $this->actingAs($manager)
            ->put(route('admin.online-inventory.update'), [
                'property_id' => $property->id,
                'room_ids' => [$room->id],
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'is_online_bookable' => false]);
    }

    public function test_dashboard_counts_online_rooms_sold(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $roomType = $this->roomType();
        $room = $this->room($property, $roomType, ['is_online_bookable' => true]);

        Booking::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'status' => Booking::STATUS_CONFIRMED,
            'source' => Booking::SOURCE_ONLINE,
            'guest_name' => 'Online Guest',
            'guest_email' => 'guest@example.com',
            'guest_phone' => '+91 90000 00001',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'nights' => 1,
            'adults' => 2,
            'children' => 0,
            'total_amount_minor' => 250000,
            'currency' => 'INR',
        ]);

        $this->actingAs($admin)
            ->withSession([\App\Support\AdminPropertyScope::SESSION_KEY => $property->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Online Guest')
            ->assertSee('Room Status');
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
     */
    private function room(Property $property, RoomType $roomType, array $overrides = []): Room
    {
        return Room::query()->create(array_merge([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => '1st Floor',
            'status' => Room::STATUS_AVAILABLE,
            'is_online_bookable' => false,
            'is_smoking' => false,
            'is_accessible' => false,
        ], $overrides));
    }
}
