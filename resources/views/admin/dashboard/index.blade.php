@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('eyebrow', $date->format('D, M j, Y'))
@section('page-title', 'Front Desk — ' . ($date->isToday() ? 'Today' : $date->format('d M Y')))

@section('header-actions')
    <span class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-black text-slate-700 shadow-sm">
        Scope: {{ $selectedPropertyName }}
    </span>
    <a href="{{ route('admin.dashboard', ['date' => $date->subDay()->toDateString()]) }}" title="Previous day">‹</a>
    <a href="{{ route('admin.dashboard', ['date' => $date->addDay()->toDateString()]) }}" title="Next day">›</a>
    <a href="{{ route('admin.bookings.create') }}">+ New Booking</a>
@endsection

@section('content')
    {{-- Band 1 · Tonight at a glance --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <article class="admin-card p-4">
            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Occupancy Tonight</p>
            <div class="mt-2 flex items-end justify-between gap-2">
                <p class="text-3xl font-black tracking-tight text-slate-950">{{ $kpis['occupancy'] }}%</p>
                <span class="text-xs font-bold text-slate-500">{{ $kpis['occupied'] }} / {{ $kpis['sellable'] }} rooms</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full {{ $kpis['occupancy'] >= 90 ? 'bg-rose-500' : ($kpis['occupancy'] >= 70 ? 'bg-amber-400' : 'bg-emerald-500') }}" style="width: {{ $kpis['occupancy'] }}%"></div>
            </div>
        </article>

        <a href="{{ route('admin.bookings.index') }}" class="admin-card block p-4 no-underline transition hover:border-sky-300">
            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">→ Arrivals</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $kpis['arrivals'] }}</p>
            <p class="mt-1 text-xs font-bold text-slate-500">check-ins expected</p>
        </a>

        <a href="{{ route('admin.bookings.index') }}" class="admin-card block p-4 no-underline transition hover:border-sky-300">
            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">← Departures</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $kpis['departures'] }}</p>
            <p class="mt-1 text-xs font-bold text-slate-500">check-outs due</p>
        </a>

        <a href="{{ route('admin.bookings.index') }}" class="admin-card block p-4 no-underline transition hover:border-sky-300">
            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">⌂ In-House</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $kpis['inHouse'] }}</p>
            <p class="mt-1 text-xs font-bold text-slate-500">staying tonight</p>
        </a>

        <a href="{{ route('admin.online-inventory.index') }}" class="admin-card block p-4 no-underline transition hover:border-sky-300">
            <p class="text-[11px] font-black uppercase tracking-wide text-sky-600">🌐 Online Channel</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $kpis['onlineSold'] }}<span class="text-base font-bold text-slate-400"> / {{ $kpis['onlineAllotment'] }}</span></p>
            <p class="mt-1 text-xs font-bold text-slate-500">online rooms sold tonight</p>
        </a>
    </div>

    {{-- Band 2 · Room status grid / portfolio --}}
    @if ($selectedProperty)
        <section class="admin-card mt-4">
            <div class="admin-card-header">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Room Status — {{ $date->isToday() ? 'Tonight' : $date->format('d M') }}</h3>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $selectedProperty->name }}</p>
                </div>
                <div class="flex items-center gap-3 text-[11px] font-bold text-slate-600">
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-sky-600"></span> Booked</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full border border-slate-300 bg-white"></span> Free</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Maint.</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-sky-600 ring-2 ring-violet-400"></span> Online</span>
                </div>
            </div>
            <div class="grid gap-3 p-4">
                @forelse ($roomGrid as $typeName => $chips)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="w-28 shrink-0 text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $typeName }}</span>
                        @foreach ($chips as $chip)
                            <span
                                class="inline-flex h-8 min-w-12 items-center justify-center rounded-lg px-2 text-xs font-black
                                    @if ($chip['state'] === 'booked') bg-sky-600 text-white @if ($chip['online']) ring-2 ring-violet-400 @endif
                                    @elseif ($chip['state'] === 'maintenance') bg-amber-100 text-amber-800
                                    @else border border-slate-200 bg-white text-slate-600 @endif"
                                title="Room {{ $chip['number'] }} — {{ $chip['state'] === 'booked' ? ($chip['online'] ? 'booked online' : 'booked') : $chip['state'] }}">
                                {{ $chip['number'] }}
                            </span>
                        @endforeach
                    </div>
                @empty
                    <p class="text-sm font-semibold text-slate-500">No rooms configured for this property yet.</p>
                @endforelse
            </div>
        </section>
    @else
        <section class="admin-card mt-4">
            <div class="admin-card-header">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Portfolio Occupancy — {{ $date->isToday() ? 'Tonight' : $date->format('d M') }}</h3>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">All properties, sorted by occupancy. Select a property in the topbar for the room-level grid.</p>
                </div>
            </div>
            <div class="grid gap-3 p-4">
                @forelse ($portfolio as $row)
                    <div class="grid items-center gap-3 sm:grid-cols-[220px_1fr_110px]">
                        <span class="truncate text-sm font-black text-slate-900">{{ $row['property']->name }}</span>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $row['rate'] >= 90 ? 'bg-rose-500' : ($row['rate'] >= 70 ? 'bg-amber-400' : 'bg-emerald-500') }}" style="width: {{ $row['rate'] }}%"></div>
                        </div>
                        <span class="text-right text-xs font-bold text-slate-600">{{ $row['occupied'] }}/{{ $row['sellable'] }} · {{ $row['rate'] }}%</span>
                    </div>
                @empty
                    <p class="text-sm font-semibold text-slate-500">No properties yet.</p>
                @endforelse
            </div>
        </section>
    @endif

    {{-- Band 3 · Next 14 nights --}}
    <section class="admin-card mt-4">
        <div class="admin-card-header">
            <div>
                <h3 class="text-sm font-black text-slate-950">Next 14 Nights</h3>
                <p class="mt-0.5 text-xs font-semibold text-slate-500">Rooms booked per night — click a bar to open that date</p>
            </div>
            <div class="flex items-center gap-3 text-[11px] font-bold text-slate-600">
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> &lt;70%</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> 70–89%</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> ≥90%</span>
            </div>
        </div>
        <div class="flex items-end gap-1.5 px-4 pb-4 pt-6" style="height: 180px;">
            @foreach ($occupancyStrip as $night)
                <a href="{{ route('admin.dashboard', ['date' => $night['date']->toDateString()]) }}"
                   class="group flex h-full flex-1 flex-col items-center justify-end gap-1.5 no-underline"
                   title="{{ $night['date']->format('D d M') }} — {{ $night['occupied'] }} room(s) booked ({{ $night['rate'] }}%)">
                    <span class="text-[10px] font-black text-slate-500 opacity-0 transition group-hover:opacity-100">{{ $night['occupied'] }}</span>
                    <span class="w-full rounded-t-md {{ $night['tone'] }} transition group-hover:opacity-80" style="height: {{ max(3, $night['rate']) }}%"></span>
                    <span class="text-[10px] font-black uppercase {{ $night['date']->isWeekend() ? 'text-sky-600' : 'text-slate-400' }}">{{ $night['date']->format('d') }}<br>{{ $night['date']->format('D') }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Band 4 · Recent bookings --}}
    <section class="admin-card mt-4 overflow-hidden">
        <div class="admin-card-header">
            <div>
                <h3 class="text-sm font-black text-slate-950">Recent Bookings</h3>
                <p class="mt-0.5 text-xs font-semibold text-slate-500">Latest booking activity</p>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="text-xs font-black text-sky-700">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Guest</th>
                        <th>Property</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Amount</th>
                        <th>Source</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentBookings as $booking)
                        <tr>
                            <td class="font-black text-slate-950">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-sky-700 no-underline hover:underline">{{ $booking->booking_number }}</a>
                            </td>
                            <td class="font-bold text-slate-800">{{ $booking->guest_name }}</td>
                            <td class="text-slate-600">{{ $booking->property?->name }}</td>
                            <td class="font-semibold text-slate-600">{{ $booking->check_in_date->format('d M') }}</td>
                            <td class="font-semibold text-slate-600">{{ $booking->check_out_date->format('d M') }}</td>
                            <td class="font-black text-slate-900">{{ $booking->formattedTotal() }}</td>
                            <td class="text-slate-600">{{ ucwords(str_replace('_', ' ', $booking->source)) }}</td>
                            <td>@include('admin.components.status-badge', ['status' => ucwords(str_replace('_', ' ', $booking->status))])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center font-semibold text-slate-500">No bookings yet — create your first booking to see it here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
