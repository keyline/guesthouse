<?php

namespace App\Services\Rooms;

use App\Models\Amenity;
use App\Models\PropertyRoomType;
use App\Models\Room;
use Illuminate\Support\Collection;

class AmenityResolver
{
    /** @return Collection<int, Amenity> */
    public function forCategory(int $propertyId, int $roomTypeId): Collection
    {
        $configuration = PropertyRoomType::query()->where('property_id', $propertyId)->where('room_type_id', $roomTypeId)->first();
        return $configuration?->amenities()->where('amenities.is_active', true)->orderBy('amenities.sort_order')->get()
            ?? collect();
    }

    /** @return Collection<int, Amenity> */
    public function forRoom(Room $room): Collection
    {
        $amenities = $this->forCategory($room->property_id, $room->room_type_id)->keyBy('id');
        foreach ($room->amenityOverrides()->with('amenity')->currentlyEffective()->get() as $override) {
            $override->state === 'missing' ? $amenities->forget($override->amenity_id) : $amenities->put($override->amenity_id, $override->amenity);
        }
        return $amenities->values();
    }
}
