<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Support\AdminPropertyScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReservationBoardPreviewController extends Controller
{
    public function __invoke(Request $request, AdminPropertyScope $scope): View
    {
        $start = $request->filled('start')
            ? CarbonImmutable::parse($request->string('start')->toString())->startOfDay()
            : CarbonImmutable::today();
        $end = $start->addDays(13);
        $properties = $scope->properties();
        $propertyId = $scope->selectedPropertyId();

        $rooms = Room::query()
            ->with(['roomType', 'bookings' => fn ($query) => $query
                ->whereIn('status', Booking::blockingStatuses())
                ->whereDate('check_in_date', '<=', $end->toDateString())
                ->whereDate('check_out_date', '>', $start->toDateString())
                ->orderBy('check_in_date')])
            ->when($propertyId, fn ($query, int $id) => $query->where('property_id', $id))
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        $bookings = Booking::query()
            ->when($propertyId, fn ($query, int $id) => $query->where('property_id', $id))
            ->whereIn('status', Booking::blockingStatuses())
            ->whereDate('check_in_date', '<=', $end->toDateString())
            ->whereDate('check_out_date', '>', $start->toDateString())
            ->get();

        $today = CarbonImmutable::today();
        $occupiedToday = $rooms->filter(fn (Room $room) => $room->bookings->contains(
            fn (Booking $booking) => $booking->check_in_date->lte($today) && $booking->check_out_date->gt($today)
        ))->count();

        return view('admin.reservation-board-preview', [
            'start' => $start,
            'end' => $end,
            'dates' => collect(range(0, 13))->map(fn (int $day) => $start->addDays($day)),
            'roomsByType' => $rooms->groupBy(fn (Room $room) => $room->roomType?->name ?? 'Other rooms'),
            'properties' => $properties,
            'propertyId' => $propertyId,
            'selectedProperty' => $properties->firstWhere('id', $propertyId),
            'arrivals' => $bookings->filter(fn (Booking $booking) => $booking->check_in_date->isSameDay($today))->count(),
            'departures' => $bookings->filter(fn (Booking $booking) => $booking->check_out_date->isSameDay($today))->count(),
            'inHouse' => $bookings->filter(fn (Booking $booking) => $booking->check_in_date->lte($today) && $booking->check_out_date->gt($today))->count(),
            'occupancy' => $rooms->count() ? (int) round(($occupiedToday / $rooms->count()) * 100) : 0,
        ]);
    }
}
