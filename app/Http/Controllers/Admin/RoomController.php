<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomImage;
use App\Models\RoomType;
use App\Models\Amenity;
use App\Models\RoomAmenityOverride;
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
            ->whereHas('roomType', fn ($query) => $query->where('status', RoomType::STATUS_ACTIVE))
            ->when($request->integer('room_type_id'), fn ($query, int $roomTypeId) => $query->where('room_type_id', $roomTypeId))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('room_type_id')
            ->orderBy('property_id')
            ->orderBy('room_number')
            ->paginate(18)
            ->withQueryString();

        return view('admin.rooms.index', [
            'rooms' => $rooms,
            'roomGroups' => $rooms->getCollection()->groupBy(fn (Room $room) => $room->roomType?->name ?? 'Unassigned Type'),
            'showProperty' => $scope->selectedPropertyId() === null,
            'selectedProperty' => $scope->selectedPropertyId()
                ? $scope->properties()->firstWhere('id', $scope->selectedPropertyId())
                : null,
            'roomTypes' => $this->roomTypes($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function create(Request $request, AdminPropertyScope $scope): View
    {
        $selectedPropertyId = $scope->selectedPropertyId();

        $room = new Room([
                'property_id' => $selectedPropertyId,
                'room_type_id' => $request->integer('room_type_id') ?: null,
                'status' => Room::STATUS_AVAILABLE,
            ]);
        $amenityData = $this->roomAmenityData($room);

        return view('admin.rooms.create', [
            'room' => $room,
            'selectedProperty' => $selectedPropertyId ? Property::query()->find($selectedPropertyId) : null,
            'roomTypes' => $this->roomTypes($scope),
            'statuses' => $this->statuses(),
            'amenities' => $amenityData['amenities'],
            'inheritedAmenityIds' => $amenityData['inheritedIds'],
            'amenityOverrides' => collect(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $room = Room::query()->create($request->attributesForModel());
        $this->syncAmenityOverrides($request, $room);
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

        $room->load('amenityOverrides');
        $amenityData = $this->roomAmenityData($room);
        return view('admin.rooms.edit', [
            'room' => $room,
            'selectedProperty' => $room->property,
            'roomTypes' => $this->roomTypes($scope),
            'statuses' => $this->statuses(),
            'amenities' => $amenityData['amenities'],
            'inheritedAmenityIds' => $amenityData['inheritedIds'],
            'amenityOverrides' => $room->amenityOverrides->keyBy('amenity_id'),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function update(RoomRequest $request, Room $room, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($room->property_id), 404);
        $room->update($request->attributesForModel());
        $this->syncAmenityOverrides($request, $room);
        $this->handleImageUpload($request, $room);

        return redirect()
            ->route('admin.rooms.edit', $room)
            ->with('status', 'Room updated successfully.');
    }

    /**
     * Lightweight inline edit used by the "Rooms in this category" modal —
     * updates only the operational basics, never amenities or images.
     */
    public function quickUpdate(Request $request, Room $room, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($room->property_id), 404);

        $validated = $request->validate([
            'room_number' => [
                'required', 'string', 'max:60',
                \Illuminate\Validation\Rule::unique('rooms', 'room_number')
                    ->where('property_id', $room->property_id)
                    ->ignore($room->id),
            ],
            'floor' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'in:'.Room::STATUS_AVAILABLE.','.Room::STATUS_MAINTENANCE.','.Room::STATUS_BLOCKED],
            'is_smoking' => ['boolean'],
            'is_accessible' => ['boolean'],
        ]);

        $room->update([
            'room_number' => $validated['room_number'],
            'floor' => $validated['floor'] ?? null,
            'status' => $validated['status'],
            'is_smoking' => $request->boolean('is_smoking'),
            'is_accessible' => $request->boolean('is_accessible'),
        ]);

        return back()->with('status', "Room {$validated['room_number']} updated.");
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

    /** @return array{amenities: \Illuminate\Support\Collection, inheritedIds: array<int, int>} */
    private function roomAmenityData(Room $room): array
    {
        $configuration = \App\Models\PropertyRoomType::query()
            ->with('amenities')->where('property_id', $room->property_id)->where('room_type_id', $room->room_type_id)->first();
        $inherited = $configuration?->amenities->where('is_active', true) ?? collect();
        $overridden = $room->exists
            ? Amenity::query()->whereHas('roomOverrides', fn ($query) => $query->where('room_id', $room->id))->get()
            : collect();

        return [
            'amenities' => $inherited->merge($overridden)->unique('id')->sortBy([['category','asc'],['sort_order','asc']])->values(),
            'inheritedIds' => $inherited->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    private function syncAmenityOverrides(Request $request, Room $room): void
    {
        $states = collect($request->input('amenity_state', []));
        $allowedIds = Amenity::query()
            ->where('is_active', true)
            ->whereIn('id', $states->keys()->map(fn ($id) => (int) $id))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
        $present = $allowedIds->filter(fn (int $id) => $states->get((string) $id, $states->get($id)) === RoomAmenityOverride::PRESENT);
        $missing = $allowedIds->filter(fn (int $id) => $states->get((string) $id, $states->get($id)) === RoomAmenityOverride::MISSING);
        $room->amenityOverrides()->delete();
        $present->each(fn (int $id) => $room->amenityOverrides()->create(['amenity_id'=>$id,'state'=>RoomAmenityOverride::PRESENT]));
        $missing->each(fn (int $id) => $room->amenityOverrides()->create(['amenity_id'=>$id,'state'=>RoomAmenityOverride::MISSING]));
    }
}
