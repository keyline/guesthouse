<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $rooms = $scope->apply(Room::query())
            ->with(['property', 'roomType'])
            ->when($request->integer('property_id'), fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
            ->when($request->integer('room_type_id'), fn ($query, int $roomTypeId) => $query->where('room_type_id', $roomTypeId))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('property_id')
            ->orderBy('room_number')
            ->paginate(18)
            ->withQueryString();

        return view('admin.rooms.index', [
            'rooms' => $rooms,
            'properties' => $this->properties($scope),
            'roomTypes' => $this->roomTypes($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function create(AdminPropertyScope $scope): View
    {
        return view('admin.rooms.create', [
            'room' => new Room([
                'property_id' => $scope->selectedPropertyId(),
                'status' => Room::STATUS_AVAILABLE,
            ]),
            'properties' => $this->properties($scope),
            'roomTypes' => $this->roomTypes($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $room = Room::query()->create($request->attributesForModel());

        return redirect()
            ->route('admin.rooms.show', $room)
            ->with('status', 'Room created successfully.');
    }

    public function show(Room $room, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($room->property_id), 404);
        $room->load(['property', 'roomType']);

        return view('admin.rooms.show', [
            'room' => $room,
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function edit(Room $room, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($room->property_id), 404);

        return view('admin.rooms.edit', [
            'room' => $room,
            'properties' => $this->properties($scope),
            'roomTypes' => $this->roomTypes($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function update(RoomRequest $request, Room $room, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($room->property_id), 404);
        $room->update($request->attributesForModel());

        return redirect()
            ->route('admin.rooms.show', $room)
            ->with('status', 'Room updated successfully.');
    }

    public function destroy(Room $room, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($room->property_id), 404);
        $room->delete();

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', 'Room deleted successfully.');
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
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            Room::STATUS_AVAILABLE => 'Available',
            Room::STATUS_MAINTENANCE => 'Maintenance',
            Room::STATUS_BLOCKED => 'Blocked',
        ];
    }
}
