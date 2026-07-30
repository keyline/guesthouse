<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyRuleSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyRuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_editor_locks_the_header_to_the_route_property(): void
    {
        $admin = $this->superAdmin();
        $property = $this->property(['name' => 'Anjali Bati']);
        $this->property(['name' => 'Unrelated Guest House']);

        $this->actingAs($admin)
            ->get(route('admin.properties.rules.edit', $property))
            ->assertOk()
            ->assertSee('Anjali Bati')
            ->assertSee('Rules for this property only')
            ->assertSee('Other properties are never changed.')
            ->assertDontSee('✓ All Properties');
    }

    public function test_saving_and_publishing_rules_never_changes_another_property(): void
    {
        $admin = $this->superAdmin();
        $first = $this->property(['name' => 'First Property']);
        $second = $this->property(['name' => 'Second Property']);

        // Opening the editor creates a draft strictly through the first
        // property's relationship.
        $this->actingAs($admin)->get(route('admin.properties.rules.edit', $first))->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.properties.rules.update', $first), [
                'rules' => [
                    'minimum_age' => [
                        'selection' => '18',
                        'message' => 'The primary guest must be at least 18 years old.',
                        'must_read' => '1',
                    ],
                ],
            ])
            ->assertRedirect();

        $firstDraft = $first->ruleSets()
            ->where('status', PropertyRuleSet::STATUS_DRAFT)
            ->with('rules')
            ->firstOrFail();

        $this->assertCount(1, $firstDraft->rules);
        $this->assertSame(0, $second->ruleSets()->count());

        $this->actingAs($admin)
            ->post(route('admin.properties.rules.publish', $first))
            ->assertRedirect(route('admin.properties.rules.edit', $first));

        $this->assertNotNull($first->fresh()->publishedRulesFor(today()));
        $this->assertNull($second->fresh()->publishedRulesFor(today()));
        $this->assertDatabaseMissing('property_rule_sets', ['property_id' => $second->id]);
    }

    private function superAdmin(): User
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
