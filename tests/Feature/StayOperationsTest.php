<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StayOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_desk_can_verify_guest_check_in_and_check_out(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        [$booking, $room] = $this->booking();
        $guest = $booking->guests()->create([
            'role' => 'primary', 'guest_type' => 'adult', 'full_name' => 'Aditi Sen',
            'nationality' => 'Indian', 'is_staying' => true, 'address_line_1' => '12 Park Street',
            'city' => 'Kolkata', 'state' => 'West Bengal', 'postal_code' => '700016', 'country' => 'India',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.stay', $booking))
            ->assertOk()
            ->assertSee('Verify ID for Aditi Sen.');

        $this->actingAs($admin)
            ->post(route('admin.bookings.guests.documents.store', [$booking, $guest]), [
                'document_type' => 'driving_licence',
                'document_number' => 'WB0123456789',
                'issuing_country' => 'India',
                'expires_at' => now()->addYear()->toDateString(),
                'front' => UploadedFile::fake()->image('licence.jpg'),
                'verified' => '1',
            ])
            ->assertSessionHasNoErrors();

        $document = $guest->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->front_path);
        $this->assertSame('••••••••6789', $document->document_number_masked);
        $this->assertSame('WB0123456789', $document->document_number_encrypted);

        $this->actingAs($admin)
            ->post(route('admin.bookings.check-in', $booking), [
                'room_id' => $room->id,
                'registration_accepted' => '1',
            ])
            ->assertRedirect(route('admin.bookings.show', $booking));

        $this->assertSame(Booking::STATUS_CHECKED_IN, $booking->fresh()->status);
        $this->assertDatabaseHas('stays', ['booking_id' => $booking->id, 'status' => 'checked_in']);

        $this->actingAs($admin)
            ->post(route('admin.bookings.check-out', $booking), [
                'balance_confirmed' => '1',
                'keys_returned' => '1',
            ])
            ->assertRedirect(route('admin.bookings.show', $booking));

        $this->assertSame(Booking::STATUS_CHECKED_OUT, $booking->fresh()->status);
        $this->assertDatabaseCount('stay_events', 2);
    }

    public function test_id_verification_is_recorded_in_the_activity_log(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        [$booking] = $this->booking();
        $guest = $booking->guests()->create([
            'role' => 'primary', 'guest_type' => 'adult', 'full_name' => 'Aditi Sen',
            'nationality' => 'Indian', 'is_staying' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.guests.documents.store', [$booking, $guest]), [
                'document_type' => 'voter_id',
                'document_number' => 'XYZ1234567890',
                'front' => UploadedFile::fake()->image('voter.jpg'),
                'verified' => '1',
            ])
            ->assertSessionHasNoErrors();

        // The document itself: who stored it, its verified status, the masked
        // number — scoped to the booking's property so property managers see it.
        $documentLog = \App\Models\AdminActivityLog::query()
            ->where('subject_type', 'GuestDocument')
            ->where('action', 'created')
            ->firstOrFail();
        $this->assertSame($admin->id, $documentLog->user_id);
        $this->assertSame($booking->property_id, $documentLog->property_id);
        $this->assertStringContainsString('Voter id', $documentLog->subject_label);
        $this->assertStringContainsString('7890', $documentLog->subject_label);
        $this->assertStringContainsString('Aditi Sen', $documentLog->subject_label);
        $this->assertSame('verified', $documentLog->new_values['verification_status'] ?? null);
        $this->assertSame($admin->id, (int) ($documentLog->new_values['verified_by'] ?? 0));
        // The raw number and file paths never reach the log.
        $this->assertArrayNotHasKey('document_number_encrypted', $documentLog->new_values);
        $this->assertArrayNotHasKey('front_path', $documentLog->new_values);

        // And the guest flipping to "ID verified" is logged as its own change.
        $guestLog = \App\Models\AdminActivityLog::query()
            ->where('subject_type', 'BookingGuest')
            ->where('action', 'updated')
            ->firstOrFail();
        $this->assertSame($booking->property_id, $guestLog->property_id);
        $this->assertSame('verified', $guestLog->new_values['id_verification_status'] ?? null);
        $this->assertStringContainsString('Aditi Sen', $guestLog->subject_label);
    }

    public function test_foreign_adult_requires_verified_passport_and_visa(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        [$booking, $room] = $this->booking();
        $guest = $booking->guests()->create([
            'role' => 'primary', 'guest_type' => 'adult', 'full_name' => 'Emma Taylor',
            'nationality' => 'British', 'is_staying' => true, 'address_line_1' => '10 High Street',
            'city' => 'London', 'state' => 'London', 'postal_code' => 'SW1A 1AA', 'country' => 'United Kingdom',
        ]);
        $guest->documents()->create(['document_type' => 'passport', 'verification_status' => 'verified']);

        $this->actingAs($admin)
            ->post(route('admin.bookings.check-in', $booking), ['room_id' => $room->id, 'registration_accepted' => '1'])
            ->assertSessionHasErrors('check_in');

        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->fresh()->status);
    }

    /** @return array{Booking, Room} */
    private function booking(): array
    {
        $property = Property::query()->create([
            'name' => 'Operations Hotel', 'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE, 'city' => 'Kolkata', 'country' => 'India',
            'address' => '1 Park Street', 'base_price_minor' => 250000, 'currency' => 'INR',
        ]);
        $type = RoomType::query()->create([
            'property_id' => $property->id, 'name' => 'Deluxe', 'code' => 'DLX-STAY',
            'status' => RoomType::STATUS_ACTIVE, 'max_adults' => 2, 'max_children' => 1,
            'base_price_minor' => 300000, 'currency' => 'INR',
        ]);
        $room = Room::query()->create([
            'property_id' => $property->id, 'room_type_id' => $type->id,
            'room_number' => '201', 'floor' => '2', 'status' => Room::STATUS_AVAILABLE,
        ]);
        $booking = Booking::query()->create([
            'property_id' => $property->id, 'room_type_id' => $type->id, 'room_id' => null,
            'status' => Booking::STATUS_CONFIRMED, 'source' => Booking::SOURCE_DIRECT,
            'guest_name' => 'Primary Guest', 'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(), 'nights' => 1,
            'adults' => 1, 'children' => 0, 'total_amount_minor' => 300000, 'currency' => 'INR',
        ]);

        return [$booking, $room];
    }
}
