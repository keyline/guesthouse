<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingRequest;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $bookings = $scope->apply(Booking::query())
            ->with(['property', 'roomType', 'room'])
            ->when($request->integer('property_id'), fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->date('from'), fn ($query, $from) => $query->where('check_out_date', '>', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->where('check_in_date', '<', $to))
            ->orderBy('check_in_date')
            ->orderBy('guest_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'properties' => $this->properties($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function create(Request $request, AdminPropertyScope $scope): View
    {
        $selectedPropertyId = $request->integer('property_id') ?: $scope->selectedPropertyId();

        return view('admin.bookings.create', [
            'booking' => new Booking([
                'property_id' => $selectedPropertyId,
                'room_type_id' => $request->integer('room_type_id') ?: null,
                'room_id' => $request->integer('room_id') ?: null,
                'status' => Booking::STATUS_CONFIRMED,
                'source' => Booking::SOURCE_DIRECT,
                'check_in_date' => now()->toDateString(),
                'check_out_date' => now()->addDay()->toDateString(),
                'adults' => 1,
                'children' => 0,
                'currency' => 'INR',
            ]),
            'properties' => $this->properties($scope),
            'roomTypes' => $this->roomTypes($scope),
            'rooms' => $this->rooms($scope),
            'guests' => $this->guests(),
            'statuses' => $this->statuses(),
            'sources' => $this->sources(),
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function store(BookingRequest $request): RedirectResponse
    {
        $booking = Booking::query()->create($request->attributesForModel());

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('status', 'Booking created successfully.');
    }

    public function show(Booking $booking, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);
        $booking->load(['property', 'roomType', 'room']);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function edit(Booking $booking, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);

        return view('admin.bookings.edit', [
            'booking' => $booking,
            'properties' => $this->properties($scope),
            'roomTypes' => $this->roomTypes($scope),
            'rooms' => $this->rooms($scope),
            'guests' => $this->guests(),
            'statuses' => $this->statuses(),
            'sources' => $this->sources(),
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function update(BookingRequest $request, Booking $booking, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);
        $booking->update($request->attributesForModel());

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('status', 'Booking updated successfully.');
    }

    public function destroy(Booking $booking, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($booking->property_id), 404);

        $booking->update([
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return redirect()
            ->route('admin.bookings.index')
            ->with('status', 'Booking cancelled successfully.');
    }

    /**
     * @return array<int, string>
     */
    private function properties(AdminPropertyScope $scope): array
    {
        return $scope->properties()->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    private function roomTypes(AdminPropertyScope $scope): array
    {
        return $scope->apply(RoomType::query())
            ->with('property')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (RoomType $roomType) => [
                $roomType->id => $roomType->name.' - '.$roomType->property->name,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function rooms(AdminPropertyScope $scope): array
    {
        return $scope->apply(Room::query())
            ->with(['property', 'roomType'])
            ->orderBy('room_number')
            ->get()
            ->mapWithKeys(fn (Room $room) => [
                $room->id => $room->room_number.' - '.$room->roomType->name.' - '.$room->property->name,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function guests(): array
    {
        return User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $guest) => [
                $guest->id => $guest->name.' - '.$guest->email,
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return (new BookingRequest)->statuses();
    }

    /**
     * @return array<string, string>
     */
    private function sources(): array
    {
        return (new BookingRequest)->sources();
    }
}
