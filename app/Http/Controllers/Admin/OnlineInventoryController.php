<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnlineInventoryController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $properties = $scope->properties();

        $propertyId = $request->integer('property_id') ?: $scope->selectedPropertyId();

        if ($propertyId) {
            abort_unless($scope->canAccessProperty($propertyId), 404);
        }

        $property = $propertyId ? $properties->firstWhere('id', $propertyId) : null;

        if (! $property) {
            return view('admin.online-inventory.index', [
                'mode' => 'overview',
                'property' => null,
                'roomGroups' => collect(),
                'propertySummaries' => $this->propertySummaries($properties),
                'navItems' => AdminNavigation::make('rooms'),
            ]);
        }

        $rooms = Room::query()
            ->where('property_id', $property->id)
            ->with('roomType')
            ->orderBy('room_number')
            ->get();

        $upcomingOnlineBookings = Booking::query()
            ->where('property_id', $property->id)
            ->whereNotNull('room_id')
            ->whereIn('status', Booking::blockingStatuses())
            ->whereDate('check_out_date', '>', now()->toDateString())
            ->get()
            ->countBy('room_id');

        $roomGroups = $rooms
            ->groupBy(fn (Room $room) => $room->roomType?->name ?? 'Unassigned Type')
            ->sortKeys()
            ->map(fn ($groupRooms) => [
                'rooms' => $groupRooms->values(),
                'total' => $groupRooms->count(),
                'online' => $groupRooms
                    ->filter(fn (Room $room) => $room->is_online_bookable && $room->status === Room::STATUS_AVAILABLE)
                    ->count(),
            ]);

        return view('admin.online-inventory.index', [
            'mode' => 'property',
            'property' => $property,
            'properties' => $properties,
            'roomGroups' => $roomGroups,
            'totalRooms' => $rooms->count(),
            'onlineRooms' => $rooms
                ->filter(fn (Room $room) => $room->is_online_bookable && $room->status === Room::STATUS_AVAILABLE)
                ->count(),
            'upcomingBookedRoomIds' => $upcomingOnlineBookings,
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function update(Request $request, AdminPropertyScope $scope): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer'],
            'room_ids' => ['nullable', 'array'],
            'room_ids.*' => ['integer'],
        ]);

        $propertyId = (int) $validated['property_id'];

        abort_unless($scope->canAccessProperty($propertyId), 404);

        $roomIds = array_map('intval', $validated['room_ids'] ?? []);

        Room::query()
            ->where('property_id', $propertyId)
            ->update(['is_online_bookable' => false]);

        if ($roomIds !== []) {
            Room::query()
                ->where('property_id', $propertyId)
                ->whereIn('id', $roomIds)
                ->update(['is_online_bookable' => true]);
        }

        $onlineCount = Room::query()
            ->where('property_id', $propertyId)
            ->onlineBookable()
            ->count();

        return redirect()
            ->route('admin.online-inventory.index', ['property_id' => $propertyId])
            ->with('status', "Online inventory updated. {$onlineCount} room(s) now sellable online.");
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Property>  $properties
     * @return \Illuminate\Support\Collection<int, array{property: Property, total: int, online: int}>
     */
    private function propertySummaries($properties)
    {
        $totals = Room::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->selectRaw('property_id, count(*) as total')
            ->selectRaw("sum(case when is_online_bookable and status = 'available' then 1 else 0 end) as online")
            ->groupBy('property_id')
            ->get()
            ->keyBy('property_id');

        return $properties->map(fn (Property $property) => [
            'property' => $property,
            'total' => (int) ($totals[$property->id]->total ?? 0),
            'online' => (int) ($totals[$property->id]->online ?? 0),
        ]);
    }
}
