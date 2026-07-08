<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function roomIsAvailable(Room $room, CarbonInterface $checkIn, CarbonInterface $checkOut, ?int $ignoreBookingId = null): bool
    {
        if ($room->status !== Room::STATUS_AVAILABLE) {
            return false;
        }

        return ! Booking::query()
            ->where('room_id', $room->id)
            ->whereIn('status', Booking::blockingStatuses())
            ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->where('check_in_date', '<', $checkOut->toDateString())
            ->where('check_out_date', '>', $checkIn->toDateString())
            ->exists();
    }

    /**
     * @return Collection<int, Room>
     */
    public function availableRooms(int $propertyId, int $roomTypeId, CarbonInterface $checkIn, CarbonInterface $checkOut, ?int $ignoreBookingId = null): Collection
    {
        return Room::query()
            ->with('roomType')
            ->where('property_id', $propertyId)
            ->where('room_type_id', $roomTypeId)
            ->where('status', Room::STATUS_AVAILABLE)
            ->orderBy('room_number')
            ->get()
            ->filter(fn (Room $room) => $this->roomIsAvailable($room, $checkIn, $checkOut, $ignoreBookingId))
            ->values();
    }

    public function bookedRoomCount(int $roomTypeId, CarbonInterface $date): int
    {
        return Booking::query()
            ->where('room_type_id', $roomTypeId)
            ->whereIn('status', Booking::blockingStatuses())
            ->where('check_in_date', '<=', $date->toDateString())
            ->where('check_out_date', '>', $date->toDateString())
            ->distinct('room_id')
            ->count('room_id');
    }
}
