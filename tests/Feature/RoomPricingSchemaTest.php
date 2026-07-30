<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DailyRate;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\RoomTypeInventory;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomPricingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_requires_at_least_one_room_from_the_board(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType] = $this->propertyWithRooms(2);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, [
                'room_ids' => [],
            ]))
            ->assertSessionHasErrors('room_ids');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_priced_from_default_rate_plan_daily_rates(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType] = $this->propertyWithRooms(2);
        $plan = $this->ratePlan($property, $roomType, 200000);

        // Override one night: 3,500.00 instead of the 2,000.00 rack rate.
        DailyRate::query()->create([
            'rate_plan_id' => $plan->id,
            'date' => now()->toDateString(),
            'price_minor' => 350000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, [
                'total_amount' => null,
                'check_in_date' => now()->toDateString(),
                'check_out_date' => now()->addDays(2)->toDateString(),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        // Night 1 = 3,500 (daily rate), night 2 = 2,000 (rack fallback).
        $this->assertDatabaseHas('bookings', [
            'rate_plan_id' => $plan->id,
            'total_amount_minor' => 550000,
        ]);
    }

    public function test_manual_price_still_allowed_without_rate_plan(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType] = $this->propertyWithRooms(1);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, [
                'total_amount' => '1234.50',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bookings', ['total_amount_minor' => 123450]);
    }

    public function test_booking_rejected_when_neither_price_nor_rate_plan_given(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType] = $this->propertyWithRooms(1);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, [
                'total_amount' => null,
            ]))
            ->assertSessionHasErrors('total_amount');
    }

    public function test_booking_confirmation_updates_inventory_rooms_sold(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType] = $this->propertyWithRooms(2);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('room_type_inventory', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'date' => now()->toDateString().' 00:00:00',
            'total_rooms' => 2,
            'rooms_sold' => 1,
        ]);
    }

    public function test_cancellation_releases_inventory(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType] = $this->propertyWithRooms(2);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType));

        $booking = Booking::query()->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.bookings.destroy', $booking), ['cancellation_reason' => 'guest_request']);

        $this->assertDatabaseHas('room_type_inventory', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'date' => now()->toDateString().' 00:00:00',
            'rooms_sold' => 0,
        ]);
    }

    public function test_booking_blocked_when_room_type_sold_out(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType] = $this->propertyWithRooms(1);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType, [
                'guest_name' => 'Second Guest',
            ]))
            ->assertSessionHasErrors('room_ids');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_stop_sell_blocks_booking_even_with_free_rooms(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType] = $this->propertyWithRooms(3);

        RoomTypeInventory::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'date' => now()->toDateString(),
            'total_rooms' => 3,
            'rooms_sold' => 0,
            'stop_sell' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), $this->bookingPayload($property, $roomType))
            ->assertSessionHasErrors('room_ids');
    }

    public function test_quote_returns_null_when_a_night_is_closed(): void
    {
        [$property, $roomType] = $this->propertyWithRooms(1);
        $plan = $this->ratePlan($property, $roomType, 200000);

        DailyRate::query()->create([
            'rate_plan_id' => $plan->id,
            'date' => now()->toDateString(),
            'price_minor' => 200000,
            'closed' => true,
        ]);

        $quote = app(AvailabilityService::class)->quote(
            $plan,
            CarbonImmutable::today(),
            CarbonImmutable::today()->addDay(),
        );

        $this->assertNull($quote);
    }

    public function test_backfill_command_seeds_plans_rates_and_inventory(): void
    {
        [$property, $roomType] = $this->propertyWithRooms(2);

        $this->artisan('rates:backfill', ['--days' => 7])->assertSuccessful();

        $this->assertDatabaseHas('rate_plans', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'code' => 'STD-EP',
            'default_price_minor' => $property->base_price_minor,
        ]);
        $this->assertDatabaseCount('daily_rates', 7);
        $this->assertDatabaseCount('room_type_inventory', 7);

        // Idempotent: a second run adds nothing.
        $this->artisan('rates:backfill', ['--days' => 7])->assertSuccessful();
        $this->assertDatabaseCount('daily_rates', 7);
        $this->assertDatabaseCount('room_type_inventory', 7);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: Property, 1: RoomType}
     */
    private function propertyWithRooms(int $roomCount): array
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
            'base_price_minor' => 250000,
            'currency' => 'INR',
            'sort_order' => 0,
        ]);

        $roomType = RoomType::query()->create([
            'name' => 'Standard Double',
            'code' => 'STD',
            'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2,
            'max_children' => 1,
            'sort_order' => 0,
        ]);

        foreach (range(1, $roomCount) as $index) {
            Room::query()->create([
                'property_id' => $property->id,
                'room_type_id' => $roomType->id,
                'room_number' => (string) (100 + $index),
                'status' => Room::STATUS_AVAILABLE,
            ]);
        }

        return [$property, $roomType];
    }

    private function ratePlan(Property $property, RoomType $roomType, int $rackRateMinor): RatePlan
    {
        return RatePlan::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)',
            'code' => 'STD-EP',
            'meal_plan' => RatePlan::MEAL_PLAN_EP,
            'is_refundable' => true,
            'default_price_minor' => $rackRateMinor,
            'currency' => 'INR',
            'status' => RatePlan::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bookingPayload(Property $property, RoomType $roomType, array $overrides = []): array
    {
        return array_merge([
            'property_id' => $property->id,
            'room_ids' => [Room::query()->where('property_id', $property->id)->orderBy('room_number')->value('id')],
            'status' => Booking::STATUS_CONFIRMED,
            'source' => Booking::SOURCE_DIRECT,
            'guest_name' => 'Walk In Guest',
            'guest_email' => 'guest@example.com',
            'guest_phone' => '+91 90000 00001',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'adults' => 2,
            'children' => 0,
            'total_amount' => '2500.00',
            'currency' => 'INR',
        ], $overrides);
    }
}
