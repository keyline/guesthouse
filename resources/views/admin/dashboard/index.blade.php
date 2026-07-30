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
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
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
            <p class="mt-1 text-xs font-bold text-slate-500">checked in now</p>
        </a>

        <a href="{{ route('admin.online-inventory.index') }}" class="admin-card block p-4 no-underline transition hover:border-sky-300">
            <p class="text-[11px] font-black uppercase tracking-wide text-sky-600">🌐 Online Channel</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $kpis['onlineSold'] }}<span class="text-base font-bold text-slate-400"> / {{ $kpis['onlineAllotment'] }}</span></p>
            <p class="mt-1 text-xs font-bold text-slate-500">online rooms sold tonight</p>
        </a>

        <article class="admin-card p-4">
            <p class="text-[11px] font-black uppercase tracking-wide text-amber-600">🍳 Breakfasts</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $kpis['breakfasts'] }}</p>
            <p class="mt-1 text-xs font-bold text-slate-500">to serve tomorrow morning</p>
        </article>
    </div>

    {{-- Band 1.5 · Front Desk — today's operational guest lists --}}
    <section class="admin-card mt-4">
        <div class="admin-card-header">
            <div>
                <h3 class="text-sm font-black text-slate-950">Front Desk — {{ $date->isToday() ? 'Today' : $date->format('d M Y') }}</h3>
                <p class="mt-0.5 text-xs font-semibold text-slate-500">Arrivals, departures and guests in-house{{ $selectedProperty ? ' · '.$selectedProperty->name : '' }}</p>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="text-xs font-black text-sky-600 hover:text-sky-700">All bookings →</a>
        </div>

        <div class="grid gap-4 p-4 lg:grid-cols-3">
            {{-- Arrivals / Due in --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/50">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2.5">
                    <span class="flex items-center gap-2 text-xs font-black uppercase tracking-wide text-amber-600">→ Arrivals</span>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-black text-amber-700">{{ $frontDesk['arrivals']->count() }}</span>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($frontDesk['arrivals'] as $booking)
                        @php($ready = $frontDesk['arrivalReady'][$booking->id] ?? false)
                        <li class="flex items-center gap-3 px-4 py-2.5">
                            <span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $ready ? 'bg-emerald-500' : 'bg-amber-400' }}" title="{{ $ready ? 'Ready to check in' : 'Needs prep (ID / occupant details)' }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-800">{{ $booking->guest_name }}</p>
                                <p class="truncate text-[11px] font-semibold text-slate-500">
                                    {{ $booking->room?->room_number ? '#'.$booking->room->room_number.' · ' : '' }}{{ $booking->roomType?->name }} · {{ $booking->adults + $booking->children }} pax
                                </p>
                            </div>
                            <a href="{{ route('admin.bookings.stay', $booking) }}" class="shrink-0 rounded-lg {{ $ready ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-500 hover:bg-amber-600' }} px-3 py-1.5 text-xs font-black text-white no-underline">
                                {{ $ready ? 'Check in' : 'Prepare' }}
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs font-semibold text-slate-400">No arrivals for this day.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Departures / Due out --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/50">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2.5">
                    <span class="flex items-center gap-2 text-xs font-black uppercase tracking-wide text-sky-600">← Departures</span>
                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-black text-sky-700">{{ $frontDesk['departures']->count() }}</span>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($frontDesk['departures'] as $booking)
                        @php($unpaid = $booking->payment_status === \App\Models\Booking::PAYMENT_UNPAID)
                        <li class="flex items-center gap-3 px-4 py-2.5">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-800">{{ $booking->guest_name }}</p>
                                <p class="truncate text-[11px] font-semibold {{ $unpaid ? 'text-rose-600' : 'text-slate-500' }}">
                                    {{ $booking->room?->room_number ? '#'.$booking->room->room_number.' · ' : '' }}{{ $booking->roomType?->name }}{{ $unpaid ? ' · ⚠ balance due' : ' · paid' }}
                                </p>
                            </div>
                            <a href="{{ route('admin.bookings.stay', $booking) }}" class="shrink-0 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-black text-white no-underline hover:bg-slate-900">Check out</a>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs font-semibold text-slate-400">No departures for this day.</li>
                    @endforelse
                </ul>
            </div>

            {{-- In-house --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/50">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2.5">
                    <span class="flex items-center gap-2 text-xs font-black uppercase tracking-wide text-emerald-600">⌂ In-house</span>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-black text-emerald-700">{{ $frontDesk['inHouse']->count() }}</span>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($frontDesk['inHouse'] as $booking)
                        <li class="flex items-center gap-3 px-4 py-2.5">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-800">{{ $booking->guest_name }}</p>
                                <p class="truncate text-[11px] font-semibold text-slate-500">
                                    {{ $booking->room?->room_number ? '#'.$booking->room->room_number.' · ' : '' }}{{ $booking->roomType?->name }} · till {{ $booking->check_out_date->format('d M') }}
                                </p>
                            </div>
                            <a href="{{ route('admin.bookings.stay', $booking) }}" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-black text-slate-700 no-underline hover:border-slate-400">View</a>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs font-semibold text-slate-400">No guests in-house.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    {{-- Band 2 · Room status (60%) + Next 14 nights (40%), side by side --}}
    <div class="mt-4 grid items-start gap-4 lg:grid-cols-[3fr_2fr]">
    @if ($selectedProperty)
        <section class="admin-card">
            <div class="admin-card-header !py-2.5">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Room Status — {{ $date->isToday() ? 'Tonight' : $date->format('d M') }}</h3>
                    <p class="mt-0.5 text-[11px] font-semibold text-slate-500">{{ $selectedProperty->name }}</p>
                </div>
                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-600">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-sky-600"></span> Booked</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full border border-slate-300 bg-white"></span> Free</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Maint.</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-sky-600 ring-2 ring-violet-400"></span> Online</span>
                </div>
            </div>
            <div class="grid gap-2 p-3">
                @forelse ($roomGrid as $typeName => $chips)
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="w-24 shrink-0 text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $typeName }}</span>
                        @foreach ($chips as $chip)
                            <span
                                class="inline-flex h-7 min-w-9 items-center justify-center rounded-md px-1.5 text-[11px] font-black
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
        <section class="admin-card">
            <div class="admin-card-header !py-2.5">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Portfolio Occupancy — {{ $date->isToday() ? 'Tonight' : $date->format('d M') }}</h3>
                    <p class="mt-0.5 text-[11px] font-semibold text-slate-500">All properties by occupancy. Pick a property in the topbar for room-level detail.</p>
                </div>
            </div>
            <div class="grid gap-2 p-3">
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

        {{-- Next 14 nights · compact occupancy mini-chart (40%) --}}
        <section class="admin-card">
            <div class="admin-card-header !py-2.5">
                <div>
                    <h3 class="text-sm font-black text-slate-950">Next 14 Nights</h3>
                    <p class="mt-0.5 text-[11px] font-semibold text-slate-500">Occupancy per night · tap a bar</p>
                </div>
                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-600">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>&lt;70</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-400"></span>70–89</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span>≥90</span>
                </div>
            </div>
            <div class="flex items-end gap-[3px] px-3 pb-3 pt-4" style="height: 132px;">
                @foreach ($occupancyStrip as $night)
                    <a href="{{ route('admin.dashboard', ['date' => $night['date']->toDateString()]) }}"
                       class="group relative flex h-full flex-1 flex-col items-center justify-end no-underline"
                       title="{{ $night['date']->format('D d M') }} — {{ $night['occupied'] }} room(s) · {{ $night['rate'] }}%">
                        <span class="pointer-events-none absolute -top-0.5 z-10 rounded bg-slate-900 px-1 py-0.5 text-[9px] font-black text-white opacity-0 transition group-hover:opacity-100">{{ $night['rate'] }}%</span>
                        <span class="w-full rounded-sm {{ $night['tone'] }} transition group-hover:opacity-75" style="height: {{ max(3, $night['rate']) }}%"></span>
                        <span class="mt-1 text-[9px] font-black leading-none {{ $night['date']->isWeekend() ? 'text-sky-600' : 'text-slate-400' }}">{{ $night['date']->format('d') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>{{-- /60-40 grid --}}

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
