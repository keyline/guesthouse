<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomTypeInventory;
use Carbon\CarbonImmutable;
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

    public function bookedRoomCount(int $roomTypeId, CarbonInterface $date, ?int $propertyId = null, ?int $ignoreBookingId = null): int
    {
        return Booking::query()
            ->where('room_type_id', $roomTypeId)
            ->when($propertyId, fn ($query, int $id) => $query->where('property_id', $id))
            ->when($ignoreBookingId, fn ($query, int $id) => $query->whereKeyNot($id))
            ->whereIn('status', Booking::blockingStatuses())
            ->where('check_in_date', '<=', $date->toDateString())
            ->where('check_out_date', '>', $date->toDateString())
            ->count();
    }

    /**
     * Sellable units of a room type for every night of the stay, honouring
     * stop_sell and manual allotment on room_type_inventory when a row exists,
     * and falling back to physical capacity minus blocking bookings otherwise.
     * Returns the minimum across the stay (the bookable quantity).
     */
    public function typeAvailability(int $propertyId, int $roomTypeId, CarbonInterface $checkIn, CarbonInterface $checkOut, ?int $ignoreBookingId = null): int
    {
        $checkIn = CarbonImmutable::parse($checkIn)->startOfDay();
        $checkOut = CarbonImmutable::parse($checkOut)->startOfDay();

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            return 0;
        }

        $inventory = RoomTypeInventory::query()
            ->where('property_id', $propertyId)
            ->where('room_type_id', $roomTypeId)
            ->whereDate('date', '>=', $checkIn->toDateString())
            ->whereDate('date', '<', $checkOut->toDateString())
            ->get()
            ->keyBy(fn (RoomTypeInventory $row) => $row->date->toDateString());

        $physicalCapacity = Room::query()
            ->where('property_id', $propertyId)
            ->where('room_type_id', $roomTypeId)
            ->where('status', Room::STATUS_AVAILABLE)
            ->count();

        $ignoredBooking = $ignoreBookingId ? Booking::query()->find($ignoreBookingId) : null;

        $minimum = PHP_INT_MAX;

        for ($night = $checkIn; $night->lessThan($checkOut); $night = $night->addDay()) {
            $row = $inventory->get($night->toDateString());

            if ($row) {
                if ($row->stop_sell) {
                    return 0;
                }

                $sold = $row->rooms_sold;

                // rooms_sold includes the booking being edited; give it back.
                if (
                    $ignoredBooking
                    && in_array($ignoredBooking->status, Booking::blockingStatuses(), true)
                    && $ignoredBooking->property_id === $propertyId
                    && $ignoredBooking->room_type_id === $roomTypeId
                    && $ignoredBooking->check_in_date->lessThanOrEqualTo($night)
                    && $ignoredBooking->check_out_date->greaterThan($night)
                ) {
                    $sold = max(0, $sold - 1);
                }

                $available = $row->total_rooms - $sold;
            } else {
                $available = $physicalCapacity
                    - $this->bookedRoomCount($roomTypeId, $night, $propertyId, $ignoreBookingId);
            }

            $minimum = min($minimum, max(0, $available));

            if ($minimum === 0) {
                return 0;
            }
        }

        return $minimum === PHP_INT_MAX ? 0 : $minimum;
    }

    /**
     * Total stay price in minor units for a rate plan: the daily_rates row per
     * night, falling back to the plan's rack rate when no row exists.
     * Returns null when any night is closed for sale on this plan.
     */
    public function quote(RatePlan $ratePlan, CarbonInterface $checkIn, CarbonInterface $checkOut): ?int
    {
        $checkIn = CarbonImmutable::parse($checkIn)->startOfDay();
        $checkOut = CarbonImmutable::parse($checkOut)->startOfDay();

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            return null;
        }

        $dailyRates = $ratePlan->dailyRates()
            ->whereDate('date', '>=', $checkIn->toDateString())
            ->whereDate('date', '<', $checkOut->toDateString())
            ->get()
            ->keyBy(fn ($rate) => $rate->date->toDateString());

        $total = 0;

        for ($night = $checkIn; $night->lessThan($checkOut); $night = $night->addDay()) {
            $rate = $dailyRates->get($night->toDateString());

            if ($rate?->closed) {
                return null;
            }

            $total += $rate?->price_minor ?? $ratePlan->default_price_minor;
        }

        return $total;
    }
}
