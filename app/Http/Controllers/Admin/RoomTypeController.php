<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomTypeRequest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Support\AdminNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoomTypeController extends Controller
{
    public function index(Request $request, \App\Support\AdminPropertyScope $scope): View
    {
        // Room-type configuration (rooms, online status, rates) is per property,
        // so this page only makes sense for a single property. When the scope is
        // "All Properties" we ask the manager to pick one first.
        if ($scope->selectedPropertyId() === null) {
            return view('admin.room-types.index', [
                'requireProperty' => true,
                'scopeProperties' => $scope->properties(),
                'navItems' => AdminNavigation::make('rooms'),
            ]);
        }

        $roomTypes = RoomType::query()
            ->withCount('rooms')
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $selectedPropertyId = $scope->selectedPropertyId();
        $propertyIds = $selectedPropertyId ? collect([$selectedPropertyId]) : $scope->properties()->pluck('id');
        $typeIds = $roomTypes->getCollection()->pluck('id');
        $roomStats = Room::query()->whereIn('property_id', $propertyIds)->whereIn('room_type_id', $typeIds)
            ->selectRaw('room_type_id, count(*) as total, count(distinct property_id) as properties')
            ->selectRaw("sum(case when status = 'available' and is_online_bookable = true then 1 else 0 end) as online")
            ->selectRaw("sum(case when status in ('maintenance','blocked') then 1 else 0 end) as attention")
            ->groupBy('room_type_id')->get()->keyBy('room_type_id');
        $rateCounts = \App\Models\RatePlan::query()->whereIn('property_id', $propertyIds)->whereIn('room_type_id', $typeIds)
            ->where('status', \App\Models\RatePlan::STATUS_ACTIVE)->selectRaw('room_type_id, count(*) as total')
            ->groupBy('room_type_id')->pluck('total', 'room_type_id');

        $categoryStats = $typeIds->mapWithKeys(fn ($id) => [$id => [
            'rooms' => (int) ($roomStats[$id]->total ?? 0), 'properties' => (int) ($roomStats[$id]->properties ?? 0),
            'online' => (int) ($roomStats[$id]->online ?? 0), 'attention' => (int) ($roomStats[$id]->attention ?? 0),
            'rate_plans' => (int) ($rateCounts[$id] ?? 0),
        ]]);

        return view('admin.room-types.index', [
            'requireProperty' => false,
            'roomTypes' => $roomTypes,
            'statuses' => $this->statuses(),
            'categoryStats' => $categoryStats,
            'contextPropertyCount' => $propertyIds->count(),
            'selectedPropertyName' => $selectedPropertyId ? $scope->properties()->firstWhere('id', $selectedPropertyId)?->name : null,
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function create(): View
    {
        return view('admin.room-types.create', [
            'roomType' => new RoomType([
                'status' => RoomType::STATUS_ACTIVE,
                'max_adults' => 2,
                'max_children' => 0,
            ]),
            'statuses' => $this->statuses(),
            'existingCodes' => RoomType::query()->pluck('code')->all(),
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

    public function show(RoomType $roomType, \App\Support\AdminPropertyScope $scope): View
    {
        $roomType->load(['rooms.property', 'propertyConfigurations.amenities']);
        $roomType->loadCount('bookings');

        // This profile is only manageable one property at a time. "All Properties"
        // shows a picker instead of every property's assignment at once.
        $selectedPropertyId = $scope->selectedPropertyId();
        if ($selectedPropertyId !== null) {
            abort_unless($scope->canAccessProperty($selectedPropertyId), 404);
        }

        return view('admin.room-types.show', [
            'roomType' => $roomType,
            'requireProperty' => $selectedPropertyId === null,
            'scopeProperties' => $scope->properties(),
            'properties' => $selectedPropertyId
                ? $scope->properties()->where('id', $selectedPropertyId)->values()
                : collect(),
            'roomAmenities' => Amenity::query()->where('is_active', true)->where('scope', Amenity::SCOPE_ROOM_CATEGORY)->orderBy('category')->orderBy('sort_order')->get(),
            'roomStatuses' => $this->roomStatuses(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function updatePropertyConfiguration(Request $request, RoomType $roomType, Property $property, \App\Support\AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($property->id), 404);
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'], 'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'], 'max_adults' => ['required', 'integer', 'min:1', 'max:20'],
            'max_children' => ['required', 'integer', 'min:0', 'max:20'], 'is_pet_friendly' => ['nullable', 'boolean'],
            'extra_bed_available' => ['nullable', 'boolean'], 'max_extra_beds' => ['nullable', 'integer', 'min:0', 'max:5'],
            'extra_bed_charge' => ['nullable', 'numeric', 'min:0', 'max:100000'], 'extra_bed_charge_basis' => ['nullable', 'in:per_night,per_stay'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', Rule::exists('amenities', 'id')->where(fn ($q) => $q->where('is_active', true)->where('scope', Amenity::SCOPE_ROOM_CATEGORY))],
        ]);

        DB::transaction(function () use ($validated, $request, $roomType, $property): void {
            $configuration = PropertyRoomType::query()->updateOrCreate(
                ['property_id' => $property->id, 'room_type_id' => $roomType->id],
                ['status' => $validated['status'], 'display_name' => $validated['display_name'] ?? null,
                    'description' => $validated['description'] ?? null, 'max_adults' => $validated['max_adults'],
                    'max_children' => $validated['max_children'], 'is_pet_friendly' => $request->boolean('is_pet_friendly'),
                    'extra_bed_available' => $request->boolean('extra_bed_available'),
                    'max_extra_beds' => $request->boolean('extra_bed_available') ? (int) ($validated['max_extra_beds'] ?? 1) : 0,
                    'extra_bed_charge_minor' => $request->boolean('extra_bed_available') ? (int) round(((float) ($validated['extra_bed_charge'] ?? 0)) * 100) : 0,
                    'extra_bed_charge_basis' => $validated['extra_bed_charge_basis'] ?? 'per_night']
            );
            $configuration->amenities()->sync(collect($validated['amenities'] ?? [])->mapWithKeys(fn ($id) => [(int) $id => ['availability_mode'=>'included','is_free'=>true,'fee_minor'=>0]])->all());
        });

        return back()->with('status', $property->name.' room-category configuration saved.');
    }

    public function edit(RoomType $roomType, \App\Support\AdminPropertyScope $scope): View
    {
        return view('admin.room-types.edit', [
            'roomType' => $roomType,
            'selectedPropertyId' => $scope->selectedPropertyId(),
            'statuses' => $this->statuses(),
            'existingCodes' => RoomType::query()->whereKeyNot($roomType->id)->pluck('code')->all(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function update(RoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        $roomType->update($request->attributesForModel());

        return redirect()
            ->route('admin.room-types.show', $roomType)
            ->with('status', 'Room type updated successfully.');
    }

    public function destroy(RoomType $roomType): RedirectResponse
    {
        if ($roomType->rooms()->exists()) {
            return back()->withErrors(['room_type' => 'Move or delete rooms before deleting this room type.']);
        }

        if ($roomType->bookings()->exists()) {
            return back()->withErrors(['room_type' => 'This room type has booking history and cannot be deleted. Deactivate it instead.']);
        }

        $roomType->delete();

        return redirect()
            ->route('admin.room-types.index')
            ->with('status', 'Room type deleted successfully.');
    }

    public function toggleStatus(RoomType $roomType): RedirectResponse
    {
        if ($roomType->status === RoomType::STATUS_ACTIVE) {
            $hasLiveBookings = $roomType->bookings()
                ->whereIn('status', \App\Models\Booking::blockingStatuses())
                ->whereDate('check_out_date', '>', now()->toDateString())
                ->exists();

            if ($hasLiveBookings) {
                return back()->withErrors(['room_type' => 'Cannot deactivate this category while current or upcoming bookings are active.']);
            }

            DB::transaction(function () use ($roomType): void {
                $roomType->update(['status' => RoomType::STATUS_INACTIVE]);
                $roomType->rooms()->update(['is_online_bookable' => false]);
            });

            return back()->with('status', 'Room type deactivated. Its rooms are hidden from active inventory and online sale.');
        }

        $roomType->update(['status' => RoomType::STATUS_ACTIVE]);

        return back()->with('status', 'Room type activated. Rooms remain offline until enabled from Online Inventory.');
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
