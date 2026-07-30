<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use App\Support\StayReadiness;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminPropertyScope $scope): View
    {
        $date = $this->resolveDate($request);

        $selectedProperty = $scope->selectedPropertyId()
            ? $scope->properties()->firstWhere('id', $scope->selectedPropertyId())
            : null;

        $rooms = $scope->apply(Room::query())->with('roomType')->get();
        $sellableRooms = $rooms->where('status', Room::STATUS_AVAILABLE);
        $onlineRooms = $sellableRooms->where('is_online_bookable', true);

        $stayingTonight = $scope->apply(Booking::query())
            ->with('ratePlan')
            ->whereIn('status', Booking::blockingStatuses())
            ->whereDate('check_in_date', '<=', $date->toDateString())
            ->whereDate('check_out_date', '>', $date->toDateString())
            ->get();

        $breakfastsTomorrow = $stayingTonight
            ->filter(fn (Booking $booking) => $booking->ratePlan && $booking->ratePlan->meal_plan !== 'ep')
            ->sum(fn (Booking $booking) => $booking->adults + $booking->children);

        $arrivals = $stayingTonight->filter(
            fn (Booking $booking) => $booking->check_in_date->isSameDay($date)
        );

        $departures = $scope->apply(Booking::query())
            ->whereIn('status', [Booking::STATUS_CHECKED_IN, Booking::STATUS_CHECKED_OUT])
            ->whereDate('check_out_date', $date->toDateString())
            ->count();

        $frontDesk = $this->frontDeskLists($scope, $date);

        $onlineSold = $stayingTonight->where('source', Booking::SOURCE_ONLINE)->count();

        $sellableCount = $sellableRooms->count();
        $occupancyRate = $sellableCount > 0
            ? min(100, (int) round($stayingTonight->count() / $sellableCount * 100))
            : 0;

        $bookedRoomIds = $stayingTonight->pluck('room_id')->filter()->flip();
        $onlineBookedRoomIds = $stayingTonight
            ->where('source', Booking::SOURCE_ONLINE)
            ->pluck('room_id')
            ->filter()
            ->flip();

        return view('admin.dashboard.index', [
            'date' => $date,
            'selectedProperty' => $selectedProperty,
            'selectedPropertyName' => $selectedProperty?->name ?? 'All Properties',
            'kpis' => [
                'occupancy' => $occupancyRate,
                'occupied' => $stayingTonight->count(),
                'sellable' => $sellableCount,
                'arrivals' => $arrivals->count(),
                'departures' => $departures,
                'inHouse' => $frontDesk['inHouse']->count(),
                'onlineSold' => $onlineSold,
                'onlineAllotment' => $onlineRooms->count(),
                'breakfasts' => $breakfastsTomorrow,
            ],
            'roomGrid' => $selectedProperty
                ? $this->roomGrid($rooms, $bookedRoomIds, $onlineBookedRoomIds)
                : collect(),
            'portfolio' => $selectedProperty ? collect() : $this->portfolio($scope, $date),
            'frontDesk' => $frontDesk,
            'occupancyStrip' => $this->occupancyStrip($scope, $date, $sellableCount),
            'recentBookings' => $scope->apply(Booking::query())
                ->with('property')
                ->latest()
                ->limit(6)
                ->get(),
            'navItems' => AdminNavigation::make('dashboard'),
        ]);
    }

    private function resolveDate(Request $request): CarbonImmutable
    {
        $raw = $request->string('date')->toString();

        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return CarbonImmutable::createFromFormat('Y-m-d', $raw)->startOfDay();
        }

        return CarbonImmutable::today();
    }

    /**
     * The three operational guest lists for the front desk: today's arrivals
     * (not yet checked in), today's departures (checked-in, due out) and the
     * guests currently in-house.
     *
     * @return array{arrivals: Collection<int, Booking>, departures: Collection<int, Booking>, inHouse: Collection<int, Booking>, arrivalReady: array<int, bool>}
     */
    private function frontDeskLists(AdminPropertyScope $scope, CarbonImmutable $date): array
    {
        $arrivals = $scope->apply(Booking::query())
            ->with(['room', 'roomType', 'guests.documents'])
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->whereDate('check_in_date', $date->toDateString())
            ->orderBy('guest_name')
            ->get();

        $departures = $scope->apply(Booking::query())
            ->with(['room', 'roomType'])
            ->where('status', Booking::STATUS_CHECKED_IN)
            ->whereDate('check_out_date', $date->toDateString())
            ->orderBy('guest_name')
            ->get();

        $inHouse = $scope->apply(Booking::query())
            ->with(['room', 'roomType'])
            ->where('status', Booking::STATUS_CHECKED_IN)
            ->whereDate('check_in_date', '<=', $date->toDateString())
            ->whereDate('check_out_date', '>', $date->toDateString())
            ->orderBy('check_out_date')
            ->get();

        return [
            'arrivals' => $arrivals,
            'departures' => $departures,
            'inHouse' => $inHouse,
            'arrivalReady' => $arrivals
                ->mapWithKeys(fn (Booking $booking) => [$booking->id => StayReadiness::for($booking)['ready']])
                ->all(),
        ];
    }

    /**
     * Rooms grouped by room type, each with tonight's booked/online state.
     *
     * @param  Collection<int, Room>  $rooms
     */
    private function roomGrid(Collection $rooms, Collection $bookedRoomIds, Collection $onlineBookedRoomIds): Collection
    {
        return $rooms
            ->sortBy('room_number', SORT_NATURAL)
            ->groupBy(fn (Room $room) => $room->roomType?->name ?? 'Unassigned Type')
            ->sortKeys()
            ->map(fn ($groupRooms) => $groupRooms->map(fn (Room $room) => [
                'number' => $room->room_number,
                'state' => match (true) {
                    $room->status !== Room::STATUS_AVAILABLE => 'maintenance',
                    $bookedRoomIds->has($room->id) => 'booked',
                    default => 'free',
                },
                'online' => $onlineBookedRoomIds->has($room->id),
            ])->values());
    }

    /**
     * Per-property occupancy for the "All Properties" super-admin view.
     */
    private function portfolio(AdminPropertyScope $scope, CarbonImmutable $date): Collection
    {
        $properties = $scope->properties();

        $sellableByProperty = Room::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->where('status', Room::STATUS_AVAILABLE)
            ->selectRaw('property_id, count(*) as total')
            ->groupBy('property_id')
            ->pluck('total', 'property_id');

        $occupiedByProperty = Booking::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->whereIn('status', Booking::blockingStatuses())
            ->whereDate('check_in_date', '<=', $date->toDateString())
            ->whereDate('check_out_date', '>', $date->toDateString())
            ->selectRaw('property_id, count(*) as total')
            ->groupBy('property_id')
            ->pluck('total', 'property_id');

        return $properties
            ->map(function (Property $property) use ($sellableByProperty, $occupiedByProperty) {
                $sellable = (int) ($sellableByProperty[$property->id] ?? 0);
                $occupied = (int) ($occupiedByProperty[$property->id] ?? 0);

                return [
                    'property' => $property,
                    'sellable' => $sellable,
                    'occupied' => $occupied,
                    'rate' => $sellable > 0 ? min(100, (int) round($occupied / $sellable * 100)) : 0,
                ];
            })
            ->sortByDesc('rate')
            ->values();
    }

    /**
     * Rooms booked per night for the next 14 nights.
     */
    private function occupancyStrip(AdminPropertyScope $scope, CarbonImmutable $date, int $sellableCount): Collection
    {
        $end = $date->addDays(14);

        $bookings = $scope->apply(Booking::query())
            ->whereIn('status', Booking::blockingStatuses())
            ->whereDate('check_out_date', '>', $date->toDateString())
            ->whereDate('check_in_date', '<', $end->toDateString())
            ->get(['check_in_date', 'check_out_date']);

        return collect(range(0, 13))->map(function (int $offset) use ($date, $bookings, $sellableCount) {
            $night = $date->addDays($offset);

            $occupied = $bookings->filter(
                fn (Booking $booking) => $booking->check_in_date->lessThanOrEqualTo($night)
                    && $booking->check_out_date->greaterThan($night)
            )->count();

            $rate = $sellableCount > 0 ? min(100, (int) round($occupied / $sellableCount * 100)) : 0;

            return [
                'date' => $night,
                'occupied' => $occupied,
                'rate' => $rate,
                'tone' => match (true) {
                    $rate >= 90 => 'bg-rose-500',
                    $rate >= 70 => 'bg-amber-400',
                    default => 'bg-emerald-500',
                },
            ];
        });
    }
}
