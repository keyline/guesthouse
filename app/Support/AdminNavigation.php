<?php

namespace App\Support;

class AdminNavigation
{
    /**
     * @return array<int, array{label: string, href: string, active: bool}>
     */
    public static function make(string $active): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => $active === 'dashboard'],
            ['label' => 'Bookings', 'href' => route('admin.bookings.index'), 'active' => $active === 'bookings'],
            ['label' => 'Properties', 'href' => route('admin.properties.index'), 'active' => $active === 'properties'],
            ['label' => 'Rooms', 'href' => route('admin.rooms.index'), 'active' => $active === 'rooms'],
            ['label' => 'Guests', 'href' => route('admin.guests.index'), 'active' => $active === 'guests'],
            ['label' => 'Reports', 'href' => '#', 'active' => $active === 'reports'],
            ['label' => 'Settings', 'href' => route('admin.settings.index'), 'active' => $active === 'settings'],
        ];
    }
}
