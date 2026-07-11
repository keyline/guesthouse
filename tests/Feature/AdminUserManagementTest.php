<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_admin_users(): void
    {
        $superAdmin = $this->adminUser();
        $manager = User::factory()->create([
            'name' => 'Property Manager One',
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.admin-users.index'))
            ->assertOk()
            ->assertSee('Admin Users')
            ->assertSee($manager->name);
    }

    public function test_property_manager_cannot_manage_admin_users(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.admin-users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_property_manager_for_multiple_properties(): void
    {
        $superAdmin = $this->adminUser();
        $firstProperty = $this->property(['name' => 'North Guest House']);
        $secondProperty = $this->property(['name' => 'South Banquet']);

        $this->actingAs($superAdmin)
            ->post(route('admin.admin-users.store'), [
                'name' => 'Operations Admin',
                'email' => 'ops-admin@example.com',
                'phone' => '+91 90000 11111',
                'role' => User::ROLE_PROPERTY_MANAGER,
                'password' => 'Password#123',
                'password_confirmation' => 'Password#123',
                'is_active' => '1',
                'property_ids' => [$firstProperty->id, $secondProperty->id],
            ])
            ->assertRedirect();

        $adminUser = User::query()->where('email', 'ops-admin@example.com')->firstOrFail();

        $this->assertTrue($adminUser->hasRole(User::ROLE_PROPERTY_MANAGER));
        $this->assertTrue($adminUser->is_active);
        $this->assertSame(
            [$firstProperty->id, $secondProperty->id],
            $adminUser->managedProperties()->orderBy('properties.id')->pluck('properties.id')->all()
        );
    }

    public function test_super_admin_can_update_property_assignments(): void
    {
        $superAdmin = $this->adminUser();
        $firstProperty = $this->property(['name' => 'Old Property']);
        $secondProperty = $this->property(['name' => 'New Property']);
        $manager = User::factory()->create([
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);
        $manager->managedProperties()->sync([$firstProperty->id]);

        $this->actingAs($superAdmin)
            ->put(route('admin.admin-users.update', $manager), [
                'name' => 'Updated Manager',
                'email' => $manager->email,
                'phone' => '',
                'role' => User::ROLE_PROPERTY_MANAGER,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
                'property_ids' => [$secondProperty->id],
            ])
            ->assertRedirect(route('admin.admin-users.show', $manager));

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'name' => 'Updated Manager',
        ]);
        $this->assertSame([$secondProperty->id], $manager->fresh()->managedProperties()->pluck('properties.id')->all());
    }

    public function test_admin_can_edit_own_profile_without_changing_access(): void
    {
        $firstProperty = $this->property(['name' => 'Assigned Property']);
        $secondProperty = $this->property(['name' => 'Unassigned Property']);
        $manager = User::factory()->create([
            'name' => 'Old Manager',
            'email' => 'old-manager@example.com',
            'phone' => '+91 90000 11111',
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
            'password' => 'OldPassword#123',
        ]);
        $manager->managedProperties()->sync([$firstProperty->id]);

        $this->actingAs($manager)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Edit Profile')
            ->assertSee('Access is managed by a super admin and cannot be edited here.');

        $this->actingAs($manager)
            ->put(route('admin.profile.update'), [
                'name' => 'Updated Manager',
                'email' => 'updated-manager@example.com',
                'phone' => '+91 90000 22222',
                'password' => 'NewPassword#123',
                'password_confirmation' => 'NewPassword#123',
                'role' => User::ROLE_SUPER_ADMIN,
                'property_ids' => [$secondProperty->id],
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('status', 'Profile updated successfully.');

        $manager->refresh();

        $this->assertSame('Updated Manager', $manager->name);
        $this->assertSame('updated-manager@example.com', $manager->email);
        $this->assertSame('+91 90000 22222', $manager->phone);
        $this->assertSame(User::ROLE_PROPERTY_MANAGER, $manager->role);
        $this->assertTrue(Hash::check('NewPassword#123', $manager->password));
        $this->assertSame([$firstProperty->id], $manager->managedProperties()->pluck('properties.id')->all());
    }

    public function test_topbar_dropdown_contains_profile_and_logout_actions(): void
    {
        $superAdmin = $this->adminUser();

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.profile.edit'), false)
            ->assertSee(route('admin.logout'), false)
            ->assertSee('Edit Profile')
            ->assertSee('Logout');
    }

    public function test_super_admin_can_deactivate_admin_user(): void
    {
        $superAdmin = $this->adminUser();
        $manager = User::factory()->create([
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.admin-users.destroy', $manager))
            ->assertRedirect(route('admin.admin-users.index'));

        $this->assertFalse($manager->fresh()->is_active);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
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
            'address' => '12 Guest Road',
            'check_in_time_minutes' => 720,
            'check_out_time_minutes' => 660,
            'currency' => 'INR',
        ], $overrides));
    }
}
