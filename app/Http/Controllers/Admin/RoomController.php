<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomImage;
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
        $this->handleImageUpload($request, $room);

        return redirect()
            ->route('admin.rooms.edit', $room)
            ->with('status', 'Room created successfully.');
    }

    public function show(Room $room, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($room->property_id), 404);

        return redirect()->route('admin.rooms.edit', $room);
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
        $this->handleImageUpload($request, $room);

        return redirect()
            ->route('admin.rooms.edit', $room)
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
     * @return array<int, Property>
     */
    private function properties(AdminPropertyScope $scope): array
    {
        return $scope->properties()->keyBy('id')->all();
    }

    /**
     * @return array<int, string>
     */
    private function roomTypes(AdminPropertyScope $scope): array
    {
        return RoomType::query()
            ->where('status', RoomType::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (RoomType $roomType) => [
                $roomType->id => $roomType->name,
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

    private function handleImageUpload(Request $request, Room $room): void
    {
        if (!$request->hasFile('room_images')) {
            return;
        }

        $files = $request->file('room_images');
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $path = $file->store("rooms/{$room->id}", 'public');
                RoomImage::create([
                    'room_id' => $room->id,
                    'path' => $path,
                    'alt_text' => $room->room_number,
                    'sort_order' => $room->images()->max('sort_order') + 1 ?? 1,
                ]);
            }
        }
    }
}
