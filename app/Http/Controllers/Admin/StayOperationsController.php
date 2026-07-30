<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\GuestDocument;
use App\Models\Stay;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\InventoryService;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StayOperationsController extends Controller
{
    public function show(Booking $booking, AdminPropertyScope $scope, AvailabilityService $availability): View
    {
        $this->authorizeBooking($booking, $scope);
        $booking->load(['property', 'roomType', 'room', 'guests.documents', 'stay.events.actor']);

        return view('admin.bookings.stay', [
            'booking' => $booking,
            'assignableRooms' => $availability->availableRooms($booking->property_id, $booking->room_type_id, $booking->check_in_date, $booking->check_out_date, $booking->id),
            'readiness' => $this->readiness($booking),
            'guestProfiles' => \App\Models\User::query()->where('role', \App\Models\User::ROLE_CUSTOMER)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'phone', 'date_of_birth', 'nationality', 'address']),
        ]);
    }

    public function addGuest(Request $request, Booking $booking, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeBooking($booking, $scope);
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'guest_type' => ['required', Rule::in(['adult', 'child'])],
            'role' => ['required', Rule::in(['primary', 'additional'])],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'max:80'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', Rule::exists(\App\Models\User::class, 'id')->where('role', \App\Models\User::ROLE_CUSTOMER)],
            'same_as_primary' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($booking, $validated): void {
            if ($validated['role'] === 'primary') {
                $booking->guests()->where('role', 'primary')->update(['role' => 'additional']);
            }
            if (($validated['same_as_primary'] ?? false) && $primary = $booking->guests()->where('role', 'primary')->first()) {
                $validated = array_merge($validated, $primary->only(['address_line_1', 'city', 'state', 'postal_code', 'country']));
            }
            unset($validated['same_as_primary']);
            $booking->guests()->create($validated + ['is_staying' => true]);
        });

        return back()->with('status', 'Staying guest added.');
    }

    public function updateGuest(Request $request, Booking $booking, BookingGuest $bookingGuest, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeBooking($booking, $scope);
        abort_unless($bookingGuest->booking_id === $booking->id, 404);
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'max:80'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
        ]);
        $bookingGuest->update($validated);

        return back()->with('status', 'Guest details updated.');
    }

    public function removeGuest(Booking $booking, BookingGuest $bookingGuest, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeBooking($booking, $scope);
        abort_unless($bookingGuest->booking_id === $booking->id, 404);
        abort_if($bookingGuest->role === 'primary', 422, 'Choose another primary guest before removing this guest.');
        abort_if($booking->status === Booking::STATUS_CHECKED_IN, 422, 'Checked-in occupants cannot be removed.');
        $bookingGuest->delete();

        return back()->with('status', 'Guest removed from this stay.');
    }

    public function addDocument(Request $request, Booking $booking, BookingGuest $bookingGuest, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeBooking($booking, $scope);
        abort_unless($bookingGuest->booking_id === $booking->id, 404);
        $validated = $request->validate([
            'document_type' => ['required', Rule::in(['passport', 'visa', 'driving_licence', 'voter_id', 'aadhaar_offline', 'other'])],
            'document_number' => ['nullable', 'string', 'max:80'],
            'issuing_country' => ['nullable', 'string', 'max:80'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'verified' => ['nullable', 'boolean'],
        ]);

        $number = trim((string) ($validated['document_number'] ?? ''));
        if ($validated['document_type'] === 'aadhaar_offline' && $number !== '') {
            $number = substr(preg_replace('/\D+/', '', $number), -4);
        }
        $folder = 'guest-documents/'.$bookingGuest->public_id;
        $verified = $request->boolean('verified');
        $document = $bookingGuest->documents()->create([
            'document_type' => $validated['document_type'],
            'document_number_encrypted' => $number ?: null,
            'document_number_masked' => $this->maskNumber($number),
            'issuing_country' => $validated['issuing_country'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'front_path' => $request->file('front')->store($folder, 'local'),
            'back_path' => $request->file('back')?->store($folder, 'local'),
            'verification_status' => $verified ? 'verified' : 'uploaded',
            'verified_by' => $verified ? $request->user()->id : null,
            'verified_at' => $verified ? now() : null,
            'retention_until' => now()->addYears(3),
        ]);

        if ($document->verification_status === 'verified') {
            $bookingGuest->update(['id_verification_status' => 'verified']);
        }

        return back()->with('status', 'Identity document stored securely.');
    }

    public function checkIn(Request $request, Booking $booking, AdminPropertyScope $scope, AvailabilityService $availability, InventoryService $inventory): RedirectResponse
    {
        $this->authorizeBooking($booking, $scope);
        $validated = $request->validate([
            'room_id' => ['required', 'integer'],
            'registration_accepted' => ['accepted'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $booking->load('guests.documents');
        $readiness = $this->readiness($booking);

        if ($readiness['blockers']) {
            return back()->withErrors(['check_in' => implode(' ', $readiness['blockers'])]);
        }

        $room = DB::transaction(function () use ($booking, $request, $validated, $availability, $inventory): \App\Models\Room {
            $room = \App\Models\Room::query()->whereKey($validated['room_id'])->where('property_id', $booking->property_id)->where('room_type_id', $booking->room_type_id)->lockForUpdate()->first();
            if (! $room || ! $availability->roomIsAvailable($room, $booking->check_in_date, $booking->check_out_date, $booking->id)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['room_id' => 'The selected room is no longer available.']);
            }
            $booking->update(['room_id' => $room->id, 'status' => Booking::STATUS_CHECKED_IN]);
            $stay = Stay::query()->updateOrCreate(['booking_id' => $booking->id], [
                'status' => 'checked_in', 'actual_check_in_at' => now(), 'checked_in_by' => $request->user()->id,
                'registration_accepted' => true, 'check_in_notes' => $validated['notes'] ?? null,
            ]);
            $booking->guests()->where('is_staying', true)->update(['checked_in_at' => now()]);
            $stay->events()->create(['actor_id' => $request->user()->id, 'event_type' => 'checked_in', 'payload' => ['room_id' => $room->id], 'created_at' => now()]);
            $inventory->syncBooking($booking);

            return $room;
        });

        return redirect()->route('admin.bookings.show', $booking)->with('status', "Checked in to room {$room->room_number}.");
    }

    public function checkOut(Request $request, Booking $booking, AdminPropertyScope $scope, InventoryService $inventory): RedirectResponse
    {
        $this->authorizeBooking($booking, $scope);
        abort_unless($booking->status === Booking::STATUS_CHECKED_IN, 422, 'Only checked-in stays can be checked out.');
        $validated = $request->validate(['balance_confirmed' => ['accepted'], 'keys_returned' => ['accepted'], 'notes' => ['nullable', 'string', 'max:2000']]);

        DB::transaction(function () use ($booking, $request, $validated, $inventory): void {
            $booking->update(['status' => Booking::STATUS_CHECKED_OUT]);
            $stay = Stay::query()->firstOrCreate(['booking_id' => $booking->id], ['public_id' => (string) \Illuminate\Support\Str::uuid()]);
            $stay->update(['status' => 'checked_out', 'actual_check_out_at' => now(), 'checked_out_by' => $request->user()->id, 'check_out_notes' => $validated['notes'] ?? null]);
            $booking->guests()->whereNotNull('checked_in_at')->update(['checked_out_at' => now()]);
            $stay->events()->create(['actor_id' => $request->user()->id, 'event_type' => 'checked_out', 'payload' => ['keys_returned' => true, 'balance_confirmed' => true], 'created_at' => now()]);
            $inventory->syncBooking($booking);
        });

        return redirect()->route('admin.bookings.show', $booking)->with('status', 'Checkout completed. Room is ready for housekeeping.');
    }

    private function readiness(Booking $booking): array
    {
        return \App\Support\StayReadiness::for($booking);
    }

    private function maskNumber(string $number): ?string
    {
        if ($number === '') return null;
        $visible = substr($number, -4);
        return str_repeat('•', max(0, strlen($number) - 4)).$visible;
    }

    private function authorizeBooking(Booking $booking, AdminPropertyScope $scope): void
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);
    }
}
