<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(AdminPropertyScope $scope): View
    {
        $selectedProperty = $scope->selectedPropertyId()
            ? $scope->properties()->firstWhere('id', $scope->selectedPropertyId())
            : null;
        $selectedPropertyName = $selectedProperty?->name ?? 'All Hotels';

        return view('admin.dashboard.index', [
            'selectedPropertyName' => $selectedPropertyName,
            'stats' => [
                [
                    'label' => 'Total Bookings',
                    'value' => '1,284',
                    'growth' => 12.4,
                    'tone' => 'blue',
                    'sparkline' => [36, 52, 44, 64, 58, 76, 88],
                    'iconPath' => 'M7 2a1 1 0 011 1v1h8V3a1 1 0 112 0v1h1a2 2 0 012 2v13a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h1V3a1 1 0 011-1zm12 8H5v9h14v-9z',
                ],
                [
                    'label' => 'Total Revenue',
                    'value' => '₹42.8L',
                    'growth' => 9.8,
                    'tone' => 'green',
                    'sparkline' => [42, 48, 55, 50, 68, 72, 84],
                    'iconPath' => 'M12 2a10 10 0 100 20 10 10 0 000-20zm1 15.93V19h-2v-1.08A4.98 4.98 0 017.5 16.5l1.13-1.65A3.23 3.23 0 0012 16c1.08 0 1.75-.43 1.75-1.08 0-.7-.55-1.02-2.18-1.5-2-.6-3.42-1.35-3.42-3.23 0-1.55 1.13-2.78 2.85-3.1V5h2v2.13c1.08.18 1.95.6 2.65 1.25l-1.12 1.62A3.02 3.02 0 0012 9c-.98 0-1.55.38-1.55.98 0 .66.6.94 2.25 1.45 2.2.68 3.35 1.55 3.35 3.28 0 1.58-1.13 2.9-3.05 3.22z',
                ],
                [
                    'label' => 'Total Guests',
                    'value' => '8,742',
                    'growth' => 6.1,
                    'tone' => 'violet',
                    'sparkline' => [28, 40, 54, 52, 61, 69, 78],
                    'iconPath' => 'M16 11a4 4 0 10-8 0 4 4 0 008 0zm-12 9a8 8 0 1116 0H4z',
                ],
                [
                    'label' => 'Occupancy Rate',
                    'value' => '78.6%',
                    'growth' => 4.7,
                    'tone' => 'amber',
                    'sparkline' => [48, 52, 57, 63, 70, 75, 79],
                    'iconPath' => 'M3 21V4a1 1 0 011-1h16a1 1 0 011 1v17h-2v-6H5v6H3zm2-8h14V5H5v8z',
                ],
                [
                    'label' => 'Avg. Daily Rate',
                    'value' => '₹4,680',
                    'growth' => -1.8,
                    'tone' => 'rose',
                    'sparkline' => [80, 74, 70, 68, 66, 61, 58],
                    'iconPath' => 'M4 4h16v2H4V4zm0 4h10v2H4V8zm0 4h16v2H4v-2zm0 4h10v2H4v-2z',
                ],
            ],
            'bookingTrend' => [42, 58, 51, 72, 64, 86, 79, 94, 88, 104, 112, 128],
            'topHotels' => [
                ['name' => 'EENNRA Salt Lake', 'bookings' => 326, 'revenue' => '₹12.4L', 'occupancy' => '86%', 'adr' => '₹5,100'],
                ['name' => 'Airport Guest Suites', 'bookings' => 288, 'revenue' => '₹9.8L', 'occupancy' => '82%', 'adr' => '₹4,720'],
                ['name' => 'EENNRA Ballygunge', 'bookings' => 241, 'revenue' => '₹8.6L', 'occupancy' => '77%', 'adr' => '₹4,280'],
                ['name' => 'EENNRA Banquet House', 'bookings' => 174, 'revenue' => '₹7.2L', 'occupancy' => '69%', 'adr' => '₹6,400'],
            ],
            'recentBookings' => [
                ['id' => 'BK-10294', 'guest' => 'Anika Roy', 'hotel' => 'EENNRA Salt Lake', 'checkIn' => '08 Jul', 'checkOut' => '10 Jul', 'amount' => '₹12,800', 'status' => 'Confirmed'],
                ['id' => 'BK-10293', 'guest' => 'Rohan Mehta', 'hotel' => 'Airport Guest Suites', 'checkIn' => '08 Jul', 'checkOut' => '09 Jul', 'amount' => '₹6,400', 'status' => 'Pending'],
                ['id' => 'BK-10292', 'guest' => 'Maya Sen', 'hotel' => 'EENNRA Banquet House', 'checkIn' => '09 Jul', 'checkOut' => '09 Jul', 'amount' => '₹58,000', 'status' => 'Confirmed'],
                ['id' => 'BK-10291', 'guest' => 'Aritra Das', 'hotel' => 'EENNRA Ballygunge', 'checkIn' => '10 Jul', 'checkOut' => '12 Jul', 'amount' => '₹9,200', 'status' => 'Cancelled'],
                ['id' => 'BK-10290', 'guest' => 'Nisha Kapoor', 'hotel' => 'Airport Guest Suites', 'checkIn' => '07 Jul', 'checkOut' => '08 Jul', 'amount' => '₹5,900', 'status' => 'No Show'],
            ],
            'statusBreakdown' => [
                ['label' => 'Confirmed', 'value' => 64, 'class' => 'bg-emerald-500'],
                ['label' => 'Pending', 'value' => 18, 'class' => 'bg-amber-500'],
                ['label' => 'Cancelled', 'value' => 11, 'class' => 'bg-rose-500'],
                ['label' => 'No Show', 'value' => 7, 'class' => 'bg-slate-400'],
            ],
            'navItems' => AdminNavigation::make('dashboard'),
        ]);
    }
}
