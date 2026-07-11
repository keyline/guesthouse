<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\RoomTypeInventory;
use App\Services\Booking\AvailabilityService;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AvailabilityCalendarController extends Controller
{
    public function __invoke(Request $request, AvailabilityService $availability, AdminPropertyScope $scope): View
    {
        $start = $request->date('start')
            ? CarbonImmutable::parse($request->date('start'))
            : CarbonImmutable::today();

        $propertyId = $request->integer('property_id') ?: $scope->selectedPropertyId();
        $dates = collect(range(0, 13))->map(fn (int $offset) => $start->addDays($offset));

        $roomTypes = RoomType::query()
            ->withCount(['rooms' => fn ($query) => $query
                ->where('status', 'available')
                ->when($propertyId, fn ($roomQuery, int $id) => $roomQuery->where('property_id', $id))])
            ->where('status', RoomType::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $inventory = $propertyId
            ? RoomTypeInventory::query()
                ->where('property_id', $propertyId)
                ->whereDate('date', '>=', $start->toDateString())
                ->whereDate('date', '<=', $start->addDays(13)->toDateString())
                ->get()
                ->keyBy(fn (RoomTypeInventory $row) => $row->room_type_id.'|'.$row->date->toDateString())
            : collect();

        $calendarRows = $roomTypes->filter(fn (RoomType $roomType): bool => $roomType->rooms_count > 0)
            ->map(function (RoomType $roomType) use ($dates, $availability, $propertyId, $inventory): array {
                return [
                    'roomType' => $roomType,
                    'days' => $dates->map(function (CarbonImmutable $date) use ($roomType, $availability, $propertyId, $inventory): array {
                        $row = $inventory->get($roomType->id.'|'.$date->toDateString());

                        if ($row) {
                            return [
                                'date' => $date,
                                'booked' => $row->rooms_sold,
                                'available' => $row->available(),
                                'stopSell' => $row->stop_sell,
                            ];
                        }

                        $booked = $availability->bookedRoomCount($roomType->id, $date, $propertyId);

                        return [
                            'date' => $date,
                            'booked' => $booked,
                            'available' => max(0, $roomType->rooms_count - $booked),
                            'stopSell' => false,
                        ];
                    }),
                ];
            });

        return view('admin.availability.index', [
            'calendarRows' => $calendarRows,
            'dates' => $dates,
            'properties' => $scope->properties()->pluck('name', 'id')->all(),
            'selectedPropertyName' => $propertyId ? $scope->properties()->firstWhere('id', $propertyId)?->name : 'All properties',
            'start' => $start,
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }
}
