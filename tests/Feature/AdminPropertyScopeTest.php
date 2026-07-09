<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPropertyScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_manager_sees_all_assigned_properties_on_property_index(): void
    {
        $firstProperty = $this->property(['name' => 'Assigned North Hotel', 'city' => 'Northscope']);
        $secondProperty = $this->property(['name' => 'Assigned South Hotel', 'city' => 'Southscope']);
        $unassignedProperty = $this->property(['name' => 'Hidden Corporate Hotel', 'city' => 'Hiddenscope']);
        $manager = User::factory()->create([
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);
        $manager->managedProperties()->sync([$firstProperty->id, $secondProperty->id]);

        $this->actingAs($manager)
            ->get(route('admin.properties.index'))
            ->assertOk()
            ->assertSee('Assigned North Hotel')
            ->assertSee('Northscope')
            ->assertSee('Assigned South Hotel')
            ->assertSee('Southscope')
            ->assertDontSee('Hiddenscope');

        $this->actingAs($manager)
            ->post(route('admin.property-context.update'), ['property_id' => $secondProperty->id])
            ->assertRedirect();

        $this->actingAs($manager)
            ->get(route('admin.properties.index'))
            ->assertOk()
            ->assertSee('Assigned North Hotel')
            ->assertSee('Northscope')
            ->assertSee('Assigned South Hotel')
            ->assertSee('Southscope')
            ->assertDontSee('Hiddenscope');
    }

    public function test_property_manager_cannot_open_unassigned_property(): void
    {
        $assignedProperty = $this->property(['name' => 'Assigned Hotel']);
        $unassignedProperty = $this->property(['name' => 'Unassigned Hotel']);
        $manager = User::factory()->create([
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);
        $manager->managedProperties()->sync([$assignedProperty->id]);

        $this->actingAs($manager)
            ->get(route('admin.properties.show', $unassignedProperty))
            ->assertNotFound();
    }

    public function test_property_manager_cannot_select_unassigned_property_context(): void
    {
        $assignedProperty = $this->property(['name' => 'Assigned Hotel']);
        $unassignedProperty = $this->property(['name' => 'Unassigned Hotel']);
        $manager = User::factory()->create([
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);
        $manager->managedProperties()->sync([$assignedProperty->id]);

        $this->actingAs($manager)
            ->post(route('admin.property-context.update'), ['property_id' => $unassignedProperty->id])
            ->assertSessionHasErrors('property_id');
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
