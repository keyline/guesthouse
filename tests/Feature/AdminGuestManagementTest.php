<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGuestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_guests(): void
    {
        $this->get('/admin/guests')->assertRedirect('/admin/login');
    }

    public function test_customer_cannot_access_admin_guests(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($customer)->get('/admin/guests')->assertForbidden();
    }

    public function test_admin_can_create_guest_profile(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.guests.store'), $this->guestPayload([
                'name' => 'Riya Sen',
                'email' => 'riya@example.com',
                'phone' => '+91 90000 00001',
                'phone_national' => '9000000001',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Riya Sen',
            'email' => 'riya@example.com',
            'phone' => '+919000000001',
            'phone_e164' => '+919000000001',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_guest_profile(): void
    {
        $admin = $this->adminUser();
        $guest = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
            'email' => 'guest@example.com',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.guests.update', $guest), $this->guestPayload([
                'name' => 'Updated Guest',
                'email' => 'guest@example.com',
                'nationality' => 'Indian',
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertRedirect(route('admin.guests.show', $guest));

        $this->assertDatabaseHas('users', [
            'id' => $guest->id,
            'name' => 'Updated Guest',
            'nationality' => 'Indian',
        ]);
    }

    public function test_guest_profile_links_historical_booking_by_email(): void
    {
        $admin = $this->adminUser();
        [$property, $roomType, $room] = $this->inventory();

        $booking = Booking::query()->create($this->bookingPayload($property, $roomType, $room, [
            'guest_email' => 'linked@example.com',
        ]));

        $this->actingAs($admin)
            ->post(route('admin.guests.store'), $this->guestPayload([
                'email' => 'linked@example.com',
            ]))
            ->assertRedirect();

        $guest = User::query()->where('email', 'linked@example.com')->firstOrFail();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'user_id' => $guest->id,
        ]);
    }

    public function test_admin_can_create_corporate_guest_with_gst_office_details(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.guests.store'), $this->guestPayload([
                'name' => 'Ananya Bose', 'email' => 'ananya@acme.example', 'customer_type' => 'corporate',
                'corporate_legal_name' => 'ACME Hospitality Private Limited',
                'corporate_trade_name' => 'ACME Hospitality', 'corporate_gstin' => '19ABCDE1234F1Z5',
                'corporate_pan' => 'ABCDE1234F', 'corporate_contact_name' => 'Accounts Desk',
                'corporate_email' => 'accounts@acme.example', 'corporate_phone' => '+91 33 4000 0000',
                'corporate_address_line_1' => '20 Business Park', 'corporate_city' => 'Kolkata',
                'corporate_state' => 'West Bengal', 'corporate_postal_code' => '700091', 'corporate_country' => 'India',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('corporates', ['gstin' => '19ABCDE1234F1Z5', 'legal_name' => 'ACME Hospitality Private Limited']);
        $this->assertDatabaseHas('users', ['email' => 'ananya@acme.example', 'customer_type' => 'corporate']);
    }

    public function test_front_desk_can_find_returning_guest_by_international_mobile(): void
    {
        $admin = $this->adminUser();
        $guest = User::factory()->create([
            'role' => User::ROLE_CUSTOMER, 'is_active' => true,
            'phone' => '+919876543210', 'phone_country_code' => '+91',
            'phone_national' => '9876543210', 'phone_e164' => '+919876543210',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.guests.lookup', ['country_code' => '+91', 'mobile' => '98765 43210']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('guest.id', $guest->id)
            ->assertJsonPath('phone_e164', '+919876543210');
    }

    public function test_duplicate_normalized_mobile_is_rejected(): void
    {
        $admin = $this->adminUser();
        User::factory()->create(['role' => User::ROLE_CUSTOMER, 'phone_e164' => '+919000000000']);

        $this->actingAs($admin)
            ->post(route('admin.guests.store'), $this->guestPayload())
            ->assertSessionHasErrors('phone_national');
    }

    public function test_customer_dashboard_shows_booking_history(): void
    {
        $guest = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
            'email' => 'portal@example.com',
        ]);
        [$property, $roomType, $room] = $this->inventory();

        Booking::query()->create($this->bookingPayload($property, $roomType, $room, [
            'user_id' => $guest->id,
            'guest_email' => 'portal@example.com',
            'guest_name' => $guest->name,
        ]));

        $this->actingAs($guest)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('My Account')
            ->assertSee('Central Guest House')
            ->assertSee('Standard Double')
            ->assertSee('Room 101');
    }

    public function test_admin_can_deactivate_guest_without_deleting_history(): void
    {
        $admin = $this->adminUser();
        $guest = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.guests.destroy', $guest))
            ->assertRedirect(route('admin.guests.index'));

        $this->assertDatabaseHas('users', [
            'id' => $guest->id,
            'is_active' => false,
        ]);
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
     * @return array<string, mixed>
     */
    private function guestPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'is_active' => '1',
            'phone' => '+91 90000 00000',
            'phone_country_code' => '+91',
            'phone_national' => '9000000000',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Female',
            'nationality' => 'Indian',
            'id_document_type' => 'Passport',
            'id_document_number' => 'P1234567',
            'address' => '12 Guest Road',
            'customer_type' => 'individual',
            'address_line_1' => '12 Guest Road',
            'address_line_2' => null,
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'postal_code' => '700016',
            'country' => 'India',
            'guest_notes' => 'Prefers quiet rooms.',
        ], $overrides);
    }

    /**
     * @return array{0: Property, 1: RoomType, 2: Room}
     */
    private function inventory(): array
    {
        $property = Property::query()->create([
            'name' => 'Central Guest House',
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
            'property_id' => $property->id,
            'name' => 'Standard Double',
            'code' => 'STD',
            'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2,
            'max_children' => 1,
            'base_price_minor' => 280000,
            'currency' => 'INR',
        ]);

        $room = Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'status' => Room::STATUS_AVAILABLE,
        ]);

        return [$property, $roomType, $room];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bookingPayload(Property $property, RoomType $roomType, Room $room, array $overrides = []): array
    {
        return array_merge([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'status' => Booking::STATUS_CONFIRMED,
            'source' => Booking::SOURCE_DIRECT,
            'guest_name' => 'Guest User',
            'guest_email' => 'guest@example.com',
            'guest_phone' => '+91 90000 00000',
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-11',
            'nights' => 1,
            'adults' => 2,
            'children' => 0,
            'total_amount_minor' => 280000,
            'currency' => 'INR',
        ], $overrides);
    }
}
