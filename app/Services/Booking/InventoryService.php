<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomTypeInventory;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class InventoryService
{
    /**
     * Recompute rooms_sold for every night in [start, end) from the bookings
     * table. Idempotent, so create/update/cancel all sync the same way and
     * counters can never drift. Manual total_rooms overrides and stop_sell
     * flags on existing rows are preserved.
     */
    public function refreshNights(int $propertyId, int $roomTypeId, CarbonInterface $start, CarbonInterface $end): void
    {
        $start = CarbonImmutable::parse($start)->startOfDay();
        $end = CarbonImmutable::parse($end)->startOfDay();

        if ($end->lessThanOrEqualTo($start)) {
            return;
        }

        $totalRooms = Room::query()
            ->where('property_id', $propertyId)
            ->where('room_type_id', $roomTypeId)
            ->where('status', Room::STATUS_AVAILABLE)
            ->count();

        $bookings = Booking::query()
            ->where('property_id', $propertyId)
            ->where('room_type_id', $roomTypeId)
            ->whereIn('status', Booking::blockingStatuses())
            ->where('check_in_date', '<', $end->toDateString())
            ->where('check_out_date', '>', $start->toDateString())
            ->get(['check_in_date', 'check_out_date']);

        for ($night = $start; $night->lessThan($end); $night = $night->addDay()) {
            $sold = $bookings->filter(
                fn (Booking $booking) => $booking->check_in_date->lessThanOrEqualTo($night)
                    && $booking->check_out_date->greaterThan($night)
            )->count();

            $row = RoomTypeInventory::query()
                ->where('property_id', $propertyId)
                ->where('room_type_id', $roomTypeId)
                ->whereDate('date', $night->toDateString())
                ->first() ?? new RoomTypeInventory([
                    'property_id' => $propertyId,
                    'room_type_id' => $roomTypeId,
                    'date' => $night->toDateString(),
                ]);

            if (! $row->exists) {
                $row->total_rooms = $totalRooms;
            }

            $row->rooms_sold = $sold;
            $row->save();
        }
    }

    /**
     * Sync inventory for a booking's stay, plus its previous stay window when
     * the booking was moved between dates, room types, or properties.
     *
     * @param  array{property_id?: int, room_type_id?: int, check_in_date?: string, check_out_date?: string}  $previous
     */
    public function syncBooking(Booking $booking, array $previous = []): void
    {
        $this->refreshNights(
            $booking->property_id,
            $booking->room_type_id,
            $booking->check_in_date,
            $booking->check_out_date,
        );

        if ($previous === []) {
            return;
        }

        $previousPropertyId = (int) ($previous['property_id'] ?? $booking->property_id);
        $previousRoomTypeId = (int) ($previous['room_type_id'] ?? $booking->room_type_id);

        $this->refreshNights(
            $previousPropertyId,
            $previousRoomTypeId,
            CarbonImmutable::parse($previous['check_in_date'] ?? $booking->check_in_date),
            CarbonImmutable::parse($previous['check_out_date'] ?? $booking->check_out_date),
        );
    }
}
