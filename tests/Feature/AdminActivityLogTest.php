<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_change_is_logged_with_old_and_new_values(): void
    {
        $admin = $this->actingAsAdmin();
        [$property, $roomType] = $this->propertyWithRoomType();

        $plan = RatePlan::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)',
            'code' => 'STD-EP',
            'default_price_minor' => 200000,
        ]);

        $plan->update(['default_price_minor' => 250000]);

        $log = AdminActivityLog::query()
            ->where('subject_type', 'RatePlan')
            ->where('action', 'updated')
            ->firstOrFail();

        $this->assertSame($admin->name, $log->user_name);
        $this->assertSame($property->id, $log->property_id);
        $this->assertSame(200000, $log->old_values['default_price_minor']);
        $this->assertSame(250000, $log->new_values['default_price_minor']);
    }

    public function test_room_creation_and_deletion_are_logged(): void
    {
        $this->actingAsAdmin();
        [$property, $roomType] = $this->propertyWithRoomType();

        $room = Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '404',
            'status' => Room::STATUS_AVAILABLE,
        ]);
        $room->delete();

        $this->assertDatabaseHas('admin_activity_logs', [
            'subject_type' => 'Room',
            'action' => 'created',
            'subject_label' => 'Room 404',
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'subject_type' => 'Room',
            'action' => 'deleted',
            'subject_label' => 'Room 404',
        ]);
    }

    private ?User $bookingCustomer = null;

    private function customer(): User
    {
        return $this->bookingCustomer ??= User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true, 'name' => 'Priya Sharma']);
    }

    public function test_online_booking_is_logged_by_the_signed_in_customer(): void
    {
        [$property, $roomType] = $this->propertyWithRoomType();

        Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'status' => Room::STATUS_AVAILABLE,
            'is_online_bookable' => true,
        ]);

        $plan = RatePlan::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)',
            'code' => 'STD-EP',
            'default_price_minor' => 200000,
        ]);

        $this->actingAs($this->customer())->post('/book', [
            'rooms' => [$plan->id => 1],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'adults' => 1,
            'children' => 0,
            'payment_mode' => 'pay_at_property',
        ])->assertRedirect();

        $log = AdminActivityLog::query()->where('subject_type', 'Booking')->firstOrFail();

        // Online bookings now require a signed-in customer, so they are the actor.
        $this->assertSame('Priya Sharma', $log->user_name);
        $this->assertSame('created', $log->action);
    }

    public function test_inventory_sync_noise_is_not_logged_but_stop_sell_is(): void
    {
        $this->actingAsAdmin();
        [$property, $roomType] = $this->propertyWithRoomType();

        $row = \App\Models\RoomTypeInventory::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'date' => now()->toDateString(),
            'total_rooms' => 2,
            'rooms_sold' => 0,
        ]);

        $this->assertDatabaseMissing('admin_activity_logs', ['subject_type' => 'RoomTypeInventory']);

        $row->update(['rooms_sold' => 1]);
        $this->assertDatabaseMissing('admin_activity_logs', ['subject_type' => 'RoomTypeInventory']);

        $row->update(['stop_sell' => true]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'subject_type' => 'RoomTypeInventory',
            'action' => 'updated',
        ]);
    }

    public function test_activity_log_page_loads_for_super_admin(): void
    {
        $this->actingAsAdmin();
        [$property, $roomType] = $this->propertyWithRoomType();

        $this->get(route('admin.activity-log.index'))
            ->assertOk()
            ->assertSee('Activity Log')
            ->assertSee('Property');
    }

    public function test_property_manager_sees_only_their_property_logs(): void
    {
        [$property, $roomType] = $this->propertyWithRoomType();
        $otherProperty = $this->property(['name' => 'Other Property']);

        $manager = User::factory()->create(['role' => User::ROLE_PROPERTY_MANAGER, 'is_active' => true]);
        $manager->managedProperties()->attach($property->id);

        AdminActivityLog::query()->create([
            'user_name' => 'Someone', 'action' => 'updated', 'subject_type' => 'RatePlan',
            'subject_id' => 1, 'subject_label' => 'Mine', 'property_id' => $property->id, 'created_at' => now(),
        ]);
        AdminActivityLog::query()->create([
            'user_name' => 'Someone', 'action' => 'updated', 'subject_type' => 'RatePlan',
            'subject_id' => 2, 'subject_label' => 'NotMine', 'property_id' => $otherProperty->id, 'created_at' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('admin.activity-log.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('NotMine');
    }

    public function test_customer_cannot_view_activity_log(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true]);

        $this->actingAs($customer)->get(route('admin.activity-log.index'))->assertForbidden();
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function property(array $overrides = []): Property
    {
        return Property::query()->create(array_merge([
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
            'base_price_minor' => 200000,
            'currency' => 'INR',
            'sort_order' => 0,
        ], $overrides));
    }

    /**
     * @return array{0: Property, 1: RoomType}
     */
    private function propertyWithRoomType(): array
    {
        return [
            $this->property(),
            RoomType::query()->create([
                'name' => 'Standard Double',
                'code' => 'STD',
                'status' => RoomType::STATUS_ACTIVE,
                'max_adults' => 2,
                'max_children' => 1,
                'sort_order' => 0,
            ]),
        ];
    }
}
