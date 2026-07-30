<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Support\AdminPropertyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_are_available_only_to_super_admins(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        $manager = User::factory()->create(['role' => User::ROLE_PROPERTY_MANAGER, 'is_active' => true]);

        $this->actingAs($superAdmin)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_booking_report_uses_header_property_scope_and_booking_date(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        [$selectedProperty, $selectedType, $selectedRoom] = $this->inventory('Selected Hotel', '101');
        [$otherProperty, $otherType, $otherRoom] = $this->inventory('Other Hotel', '201');

        $included = $this->booking($selectedProperty, $selectedType, $selectedRoom, 'Included Guest', [
            'total_amount_minor' => 300000,
            'discount_amount_minor' => 20000,
            'tax_amount_minor' => 10000,
        ]);
        $included->forceFill(['created_at' => '2026-07-10 10:00:00'])->saveQuietly();

        $wrongProperty = $this->booking($otherProperty, $otherType, $otherRoom, 'Wrong Property Guest');
        $wrongProperty->forceFill(['created_at' => '2026-07-10 11:00:00'])->saveQuietly();

        $wrongDate = $this->booking($selectedProperty, $selectedType, $selectedRoom, 'Wrong Date Guest');
        $wrongDate->forceFill(['created_at' => '2026-06-01 10:00:00'])->saveQuietly();

        $this->actingAs($admin)
            ->withSession([AdminPropertyScope::SESSION_KEY => $selectedProperty->id])
            ->get(route('admin.reports.index', ['tab' => 'bookings', 'from' => '2026-07-10', 'to' => '2026-07-10']))
            ->assertOk()
            ->assertSee('Property scope:')
            ->assertSee('Selected Hotel')
            ->assertSee('Included Guest')
            ->assertSee('INR 2,900.00')
            ->assertDontSee('Wrong Property Guest')
            ->assertDontSee('Wrong Date Guest');
    }

    public function test_payment_report_uses_transaction_date_and_property_scope(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        [$selectedProperty, $selectedType, $selectedRoom] = $this->inventory('Selected Hotel', '101');
        [$otherProperty, $otherType, $otherRoom] = $this->inventory('Other Hotel', '201');
        $includedBooking = $this->booking($selectedProperty, $selectedType, $selectedRoom, 'Paid Guest');
        $otherBooking = $this->booking($otherProperty, $otherType, $otherRoom, 'Other Paid Guest');

        Payment::query()->create([
            'booking_id' => $includedBooking->id,
            'gateway' => 'manual',
            'method' => 'upi',
            'status' => Payment::STATUS_CAPTURED,
            'amount_minor' => 150000,
            'currency' => 'INR',
            'paid_at' => '2026-07-11 09:30:00',
        ]);
        Payment::query()->create([
            'booking_id' => $otherBooking->id,
            'gateway' => 'manual',
            'method' => 'cash',
            'status' => Payment::STATUS_CAPTURED,
            'amount_minor' => 99900,
            'currency' => 'INR',
            'paid_at' => '2026-07-11 10:30:00',
        ]);
        Payment::query()->create([
            'booking_id' => $includedBooking->id,
            'gateway' => 'manual',
            'method' => 'card',
            'status' => Payment::STATUS_CAPTURED,
            'amount_minor' => 50000,
            'currency' => 'INR',
            'paid_at' => '2026-06-01 10:30:00',
        ]);

        $this->actingAs($admin)
            ->withSession([AdminPropertyScope::SESSION_KEY => $selectedProperty->id])
            ->get(route('admin.reports.index', ['tab' => 'payments', 'from' => '2026-07-11', 'to' => '2026-07-11']))
            ->assertOk()
            ->assertSee('Paid Guest')
            ->assertSee('UPI')
            ->assertSee('INR 1,500.00')
            ->assertDontSee('Other Paid Guest')
            ->assertDontSee('INR 999.00')
            ->assertDontSee('Card');
    }

    /** @return array{Property, RoomType, Room} */
    private function inventory(string $propertyName, string $roomNumber): array
    {
        $property = Property::query()->create([
            'name' => $propertyName,
            'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE,
            'city' => 'Kolkata',
            'country' => 'India',
            'address' => '12 Guest Road',
            'check_in_time_minutes' => 720,
            'check_out_time_minutes' => 660,
            'base_price_minor' => 250000,
            'currency' => 'INR',
        ]);
        $roomType = RoomType::query()->create([
            'name' => $propertyName.' Standard',
            'code' => str($propertyName)->slug().'-standard',
            'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2,
            'max_children' => 1,
        ]);
        $room = Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => $roomNumber,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        return [$property, $roomType, $room];
    }

    /** @param array<string, mixed> $overrides */
    private function booking(Property $property, RoomType $roomType, Room $room, string $guestName, array $overrides = []): Booking
    {
        return Booking::query()->create(array_merge([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'status' => Booking::STATUS_CONFIRMED,
            'source' => Booking::SOURCE_DIRECT,
            'guest_name' => $guestName,
            'guest_phone' => '+91 90000 00000',
            'check_in_date' => '2026-07-20',
            'check_out_date' => '2026-07-21',
            'nights' => 1,
            'adults' => 2,
            'children' => 0,
            'total_amount_minor' => 200000,
            'currency' => 'INR',
        ], $overrides));
    }
}
