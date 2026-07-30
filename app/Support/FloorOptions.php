<?php

namespace App\Support;

class FloorOptions
{
    /**
     * The selectable floor labels. Configured in config/hotel.php so the list
     * is a single backend setting the operator can adjust.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $floors = config('hotel.floors', []);

        return array_values(array_filter(array_map('strval', is_array($floors) ? $floors : [])));
    }
}
