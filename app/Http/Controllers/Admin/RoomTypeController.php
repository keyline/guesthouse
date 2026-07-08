<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomTypeRequest;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $roomTypes = $scope->apply(RoomType::query())
            ->with('property')
            ->withCount('rooms')
            ->when($request->integer('property_id'), fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.room-types.index', [
            'roomTypes' => $roomTypes,
            'properties' => $this->properties($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function create(AdminPropertyScope $scope): View
    {
        return view('admin.room-types.create', [
            'roomType' => new RoomType([
                'property_id' => $scope->selectedPropertyId(),
                'status' => RoomType::STATUS_ACTIVE,
                'max_adults' => 2,
                'max_children' => 0,
                'base_occupancy' => 2,
                'currency' => 'INR',
            ]),
            'properties' => $this->properties($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function store(RoomTypeRequest $request): RedirectResponse
    {
        $roomType = RoomType::query()->create($request->attributesForModel());

        return redirect()
            ->route('admin.room-types.show', $roomType)
            ->with('status', 'Room type created successfully.');
    }

    public function show(RoomType $roomType, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($roomType->property_id), 404);
        $roomType->load(['property', 'rooms']);

        return view('admin.room-types.show', [
            'roomType' => $roomType,
            'roomStatuses' => $this->roomStatuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function edit(RoomType $roomType, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($roomType->property_id), 404);

        return view('admin.room-types.edit', [
            'roomType' => $roomType,
            'properties' => $this->properties($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function update(RoomTypeRequest $request, RoomType $roomType, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($roomType->property_id), 404);
        $roomType->update($request->attributesForModel());

        return redirect()
            ->route('admin.room-types.show', $roomType)
            ->with('status', 'Room type updated successfully.');
    }

    public function destroy(RoomType $roomType, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($roomType->property_id), 404);

        if ($roomType->rooms()->exists()) {
            return back()->withErrors(['room_type' => 'Move or delete rooms before deleting this room type.']);
        }

        $roomType->delete();

        return redirect()
            ->route('admin.room-types.index')
            ->with('status', 'Room type deleted successfully.');
    }

    /**
     * @return array<int, string>
     */
    private function properties(AdminPropertyScope $scope): array
    {
        return $scope->properties()->pluck('name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            RoomType::STATUS_ACTIVE => 'Active',
            RoomType::STATUS_INACTIVE => 'Inactive',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function roomStatuses(): array
    {
        return [
            Room::STATUS_AVAILABLE => 'Available',
            Room::STATUS_MAINTENANCE => 'Maintenance',
            Room::STATUS_BLOCKED => 'Blocked',
        ];
    }
}
