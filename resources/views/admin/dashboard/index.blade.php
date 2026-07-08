@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('eyebrow', now()->format('M j, Y'))
@section('page-title', 'Hotel Operations Dashboard')

@section('header-actions')
    <span class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-black text-slate-700 shadow-sm">
        Scope: {{ $selectedPropertyName }}
    </span>
    <a href="{{ route('admin.bookings.create') }}">+ New Booking</a>
@endsection

@section('content')
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($stats as $stat)
            @include('admin.components.stat-card', ['stat' => $stat])
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-[1.55fr_0.95fr]">
        @component('admin.components.chart-card', ['title' => 'Booking Trend', 'subtitle' => 'Monthly reservation volume', 'action' => 'Last 12 months'])
            <div class="flex h-64 items-end gap-2">
                @foreach ($bookingTrend as $index => $value)
                    @php $height = max(18, min(100, round(($value / max($bookingTrend)) * 100))); @endphp
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <div class="relative flex h-52 w-full items-end rounded-t-lg bg-slate-50">
                            <div class="w-full rounded-t-lg bg-gradient-to-t from-sky-700 to-sky-400 shadow-sm" style="height: {{ $height }}%"></div>
                        </div>
                        <span class="text-[10px] font-black uppercase text-slate-400">{{ now()->subMonths(11 - $index)->format('M') }}</span>
                    </div>
                @endforeach
            </div>
        @endcomponent

        @component('admin.components.chart-card', ['title' => 'Booking Status', 'subtitle' => 'Current booking mix'])
            <div class="grid gap-4 md:grid-cols-[160px_1fr] xl:grid-cols-1">
                <div class="mx-auto grid h-40 w-40 place-items-center rounded-full bg-[conic-gradient(#10b981_0_64%,#f59e0b_64%_82%,#ef4444_82%_93%,#94a3b8_93%_100%)]">
                    <div class="grid h-24 w-24 place-items-center rounded-full bg-white text-center shadow-inner">
                        <span>
                            <span class="block text-2xl font-black text-slate-950">1,284</span>
                            <span class="block text-[10px] font-black uppercase tracking-wide text-slate-500">Bookings</span>
                        </span>
                    </div>
                </div>
                <div class="grid content-center gap-2">
                    @foreach ($statusBreakdown as $status)
                        <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <span class="flex items-center gap-2 text-sm font-bold text-slate-700">
                                <span class="h-2.5 w-2.5 rounded-full {{ $status['class'] }}"></span>
                                {{ $status['label'] }}
                            </span>
                            <span class="text-sm font-black text-slate-950">{{ $status['value'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endcomponent
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-[0.95fr_1.55fr]">
        <section class="admin-card overflow-hidden">
            <div class="admin-card-header">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Top Performing Hotels</h3>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">By bookings and revenue</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Hotel</th>
                            <th>Bookings</th>
                            <th>Revenue</th>
                            <th>Occ.</th>
                            <th>ADR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topHotels as $hotel)
                            <tr>
                                <td class="font-black text-slate-900">{{ $hotel['name'] }}</td>
                                <td class="font-bold text-slate-700">{{ $hotel['bookings'] }}</td>
                                <td class="font-bold text-slate-700">{{ $hotel['revenue'] }}</td>
                                <td class="font-bold text-slate-700">{{ $hotel['occupancy'] }}</td>
                                <td class="font-bold text-slate-700">{{ $hotel['adr'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card overflow-hidden">
            <div class="admin-card-header">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Recent Bookings</h3>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">Latest booking activity across hotels</p>
                </div>
                <a href="{{ route('admin.bookings.index') }}" class="text-xs font-black text-sky-700">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Guest Name</th>
                            <th>Hotel</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentBookings as $booking)
                            <tr>
                                <td class="font-black text-slate-950">{{ $booking['id'] }}</td>
                                <td class="font-bold text-slate-800">{{ $booking['guest'] }}</td>
                                <td class="text-slate-600">{{ $booking['hotel'] }}</td>
                                <td class="font-semibold text-slate-600">{{ $booking['checkIn'] }}</td>
                                <td class="font-semibold text-slate-600">{{ $booking['checkOut'] }}</td>
                                <td class="font-black text-slate-900">{{ $booking['amount'] }}</td>
                                <td>@include('admin.components.status-badge', ['status' => $booking['status']])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Management Overview</h3>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">Compact operational priorities</p>
                </div>
            </div>
            <div class="grid gap-2 p-4 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-black uppercase text-slate-500">Check-ins</p>
                    <p class="mt-1 text-lg font-black text-slate-950">22</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-black uppercase text-slate-500">Rooms to Clean</p>
                    <p class="mt-1 text-lg font-black text-slate-950">14</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-black uppercase text-slate-500">Payment Due</p>
                    <p class="mt-1 text-lg font-black text-slate-950">7</p>
                </div>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Active Tasks</h3>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">Operations Queue</p>
                </div>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach (['Confirm pending advances', 'Assign housekeeping rooms', 'Reply to booking inquiries'] as $task)
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <p class="text-sm font-bold text-slate-800">{{ $task }}</p>
                        <span class="rounded-full bg-sky-50 px-2 py-1 text-[11px] font-black text-sky-700 ring-1 ring-sky-100">Open</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
