<?php

namespace Tests\Feature;

use App\Models\DailyRate;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRatePlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_page_lists_room_types_and_plans(): void
    {
        $admin = $this->admin();
        [$property, , $plan] = $this->setup_();

        $this->actingAs($admin)
            ->get(route('admin.rate-plans.index', ['property_id' => $property->id]))
            ->assertOk()
            ->assertSee('Room Types & Pricing')
            ->assertSee('Deluxe Double')
            ->assertSee($plan->name);
    }

    public function test_creating_breakfast_plan_seeds_prices_from_ep_plus_supplement(): void
    {
        $admin = $this->admin();
        [$property, $roomType, $ep] = $this->setup_();

        DailyRate::query()->create([
            'rate_plan_id' => $ep->id,
            'date' => now()->addDay()->toDateString(),
            'price_minor' => 300000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.rate-plans.store'), [
                'property_id' => $property->id,
                'room_type_id' => $roomType->id,
                'meal_plan' => RatePlan::MEAL_PLAN_CP,
                'amount' => '300',
            ])
            ->assertRedirect();

        $cp = RatePlan::query()->where('meal_plan', RatePlan::MEAL_PLAN_CP)->firstOrFail();

        $this->assertSame('With Breakfast (CP)', $cp->name);
        $this->assertSame(200000 + 30000, $cp->default_price_minor);
        $this->assertDatabaseHas('daily_rates', [
            'rate_plan_id' => $cp->id,
            'price_minor' => 330000,
        ]);
    }

    public function test_duplicate_meal_plan_is_rejected(): void
    {
        $admin = $this->admin();
        [$property, $roomType] = $this->setup_();

        $this->actingAs($admin)
            ->post(route('admin.rate-plans.store'), [
                'property_id' => $property->id,
                'room_type_id' => $roomType->id,
                'meal_plan' => RatePlan::MEAL_PLAN_EP,
                'amount' => '1500',
            ])
            ->assertSessionHasErrors('meal_plan');
    }

    public function test_rack_price_update_rolls_onto_seeded_future_rates_only(): void
    {
        $admin = $this->admin();
        [, , $ep] = $this->setup_();

        $seeded = DailyRate::query()->create([
            'rate_plan_id' => $ep->id,
            'date' => now()->addDay()->toDateString(),
            'price_minor' => 200000,
        ]);
        $override = DailyRate::query()->create([
            'rate_plan_id' => $ep->id,
            'date' => now()->addDays(2)->toDateString(),
            'price_minor' => 350000,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.rate-plans.update', $ep), ['price' => '2400'])
            ->assertRedirect();

        $this->assertSame(240000, $ep->fresh()->default_price_minor);
        $this->assertSame(240000, $seeded->fresh()->price_minor);
        $this->assertSame(350000, $override->fresh()->price_minor);
    }

    public function test_deactivated_plan_disappears_from_booking_engine(): void
    {
        $admin = $this->admin();
        [$property, , $ep] = $this->setup_();

        $this->actingAs($admin)->post(route('admin.rate-plans.toggle', $ep))->assertRedirect();

        $this->assertSame(RatePlan::STATUS_INACTIVE, $ep->fresh()->status);

        $this->get('/book?property_id='.$property->id.'&check_in='.now()->toDateString().'&check_out='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertSee('No rooms available online');
    }

    public function test_property_manager_cannot_manage_other_property_plans(): void
    {
        [, $roomType, $ep] = $this->setup_();
        $manager = User::factory()->create(['role' => User::ROLE_PROPERTY_MANAGER, 'is_active' => true]);

        $this->actingAs($manager)
            ->put(route('admin.rate-plans.update', $ep), ['price' => '9999'])
            ->assertNotFound();
    }

    public function test_dashboard_counts_breakfasts_for_cp_guests(): void
    {
        $admin = $this->admin();
        [$property, $roomType, $ep] = $this->setup_();

        $cp = RatePlan::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'With Breakfast (CP)',
            'code' => 'CP',
            'meal_plan' => RatePlan::MEAL_PLAN_CP,
            'default_price_minor' => 230000,
        ]);

        \App\Models\Booking::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'rate_plan_id' => $cp->id,
            'status' => \App\Models\Booking::STATUS_CONFIRMED,
            'source' => \App\Models\Booking::SOURCE_ONLINE,
            'guest_name' => 'CP Guest',
            'guest_phone' => '+91 90000 00001',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'nights' => 1,
            'adults' => 2,
            'children' => 1,
            'total_amount_minor' => 230000,
            'currency' => 'INR',
        ]);

        $this->actingAs($admin)
            ->withSession([\App\Support\AdminPropertyScope::SESSION_KEY => $property->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Breakfasts')
            ->assertSee('to serve tomorrow morning');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
    }

    /**
     * @return array{0: Property, 1: RoomType, 2: RatePlan}
     */
    private function setup_(): array
    {
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
            'base_price_minor' => 200000,
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

        Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'status' => Room::STATUS_AVAILABLE,
            'is_online_bookable' => true,
        ]);

        $ep = RatePlan::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)',
            'code' => 'EP',
            'meal_plan' => RatePlan::MEAL_PLAN_EP,
            'is_refundable' => true,
            'default_price_minor' => 200000,
            'currency' => 'INR',
            'status' => RatePlan::STATUS_ACTIVE,
        ]);

        return [$property, $roomType, $ep];
    }
}
