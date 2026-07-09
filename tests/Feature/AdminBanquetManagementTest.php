<?php

namespace Tests\Feature;

use App\Models\Banquet;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBanquetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_banquet_and_stays_on_edit_page_with_status(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $banquet = Banquet::query()->create([
            'property_id' => $property->id,
            'name' => 'Old Banquet',
            'description' => 'Old description',
            'capacity_min' => 50,
            'capacity_max' => 100,
            'base_price_minor' => 2000,
            'currency' => 'INR',
            'setup_types' => ['banquet'],
            'status' => Banquet::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.banquets.update', $banquet), [
                'property_id' => $property->id,
                'name' => 'Updated Banquet',
                'description' => 'Updated description',
                'capacity_min' => 75,
                'capacity_max' => 150,
                'base_price_minor' => 2500,
                'currency' => 'INR',
                'setup_types' => ['banquet', 'theatre'],
                'amenities' => [],
                'status' => Banquet::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.banquets.edit', $banquet))
            ->assertSessionHas('status', 'Banquet updated successfully.');

        $this->assertDatabaseHas('banquets', [
            'id' => $banquet->id,
            'name' => 'Updated Banquet',
            'description' => 'Updated description',
            'capacity_min' => 75,
            'capacity_max' => 150,
            'base_price_minor' => 2500,
        ]);
    }

    public function test_banquet_success_modal_renders_close_controls(): void
    {
        $admin = $this->adminUser();
        $property = $this->property();
        $banquet = Banquet::query()->create([
            'property_id' => $property->id,
            'name' => 'Modal Banquet',
            'capacity_min' => 50,
            'capacity_max' => 100,
            'base_price_minor' => 2000,
            'currency' => 'INR',
            'setup_types' => ['banquet'],
            'status' => Banquet::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->withSession(['status' => 'Banquet updated successfully.'])
            ->get(route('admin.banquets.edit', $banquet))
            ->assertOk()
            ->assertSee('Banquet updated successfully.')
            ->assertSee('data-success-modal', false)
            ->assertSee('data-success-modal-close', false)
            ->assertSee('position: absolute; top: 16px; right: 16px;', false);
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
            'location' => 'Golpark',
            'address' => '12 Guest Road',
            'phone' => '+91 90000 00000',
            'email' => 'property@example.com',
            'manager_name' => 'Front Office Manager',
            'check_in_time_minutes' => 720,
            'check_out_time_minutes' => 660,
            'base_price_minor' => 250000,
            'currency' => 'INR',
            'sort_order' => 0,
            'description' => 'Designed for quick booking and guest comfort.',
        ]);
    }
}
