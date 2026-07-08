<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RoomType;
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

        $roomTypes = $scope->apply(RoomType::query())
            ->with('property')
            ->withCount(['rooms' => fn ($query) => $query->where('status', 'available')])
            ->when($propertyId, fn ($query, int $id) => $query->where('property_id', $id))
            ->where('status', RoomType::STATUS_ACTIVE)
            ->orderBy('property_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $calendarRows = $roomTypes->map(function (RoomType $roomType) use ($dates, $availability): array {
            return [
                'roomType' => $roomType,
                'days' => $dates->map(function (CarbonImmutable $date) use ($roomType, $availability): array {
                    $booked = $availability->bookedRoomCount($roomType->id, $date);
                    $available = max(0, $roomType->rooms_count - $booked);

                    return [
                        'date' => $date,
                        'booked' => $booked,
                        'available' => $available,
                    ];
                }),
            ];
        });

        return view('admin.availability.index', [
            'calendarRows' => $calendarRows,
            'dates' => $dates,
            'properties' => $scope->properties()->pluck('name', 'id')->all(),
            'start' => $start,
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }
}
