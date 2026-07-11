@extends('admin.layouts.app')

@section('title', 'Availability')
@section('eyebrow', 'Availability Calendar')
@section('page-title', '14-Day Availability')

@section('header-actions')
    <a href="{{ route('admin.bookings.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Bookings</a>
    <a href="{{ route('admin.bookings.create') }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">New Booking</a>
@endsection

@section('content')
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.availability.index') }}" class="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
            <div>
                <label for="property_id" class="text-sm font-bold text-slate-700">Property</label>
                <select id="property_id" name="property_id" class="mt-2 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">All properties</option>
                    @foreach ($properties as $id => $name)
                        <option value="{{ $id }}" @selected((int) request('property_id') === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="start" class="text-sm font-bold text-slate-700">Start date</label>
                <input id="start" name="start" type="date" value="{{ $start->toDateString() }}" class="mt-2 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button class="h-10 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Show</button>
                <a href="{{ route('admin.availability.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Today</a>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="sticky left-0 bg-slate-50 px-5 py-3">Room Type</th>
                        @foreach ($dates as $date)
                            <th class="px-3 py-3 text-center">{{ $date->format('M j') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($calendarRows as $row)
                        <tr>
                            <td class="sticky left-0 bg-white px-5 py-4">
                                <span class="block font-black">{{ $row['roomType']->name }}</span>
                                <span class="text-xs font-semibold text-slate-500">{{ $selectedPropertyName }}</span>
                            </td>
                            @foreach ($row['days'] as $day)
                                @php
                                    $stopSell = $day['stopSell'] ?? false;
                                    $tone = $stopSell
                                        ? 'bg-slate-200 text-slate-600'
                                        : ($day['available'] > 2 ? 'bg-emerald-50 text-emerald-700' : ($day['available'] > 0 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700'));
                                @endphp
                                <td class="px-3 py-4 text-center">
                                    <span class="inline-flex min-w-12 justify-center rounded-full px-2.5 py-1 text-xs font-black {{ $tone }}" @if ($stopSell) title="Stop-sell: closed for new bookings" @endif>{{ $stopSell ? '✕' : $day['available'] }}</span>
                                    <span class="mt-1 block text-[11px] font-semibold text-slate-400">{{ $stopSell ? 'stop-sell' : $day['booked'].' booked' }}</span>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="px-5 py-10 text-center">
                                <h2 class="text-xl font-black">No active room types</h2>
                                <p class="mt-2 text-sm font-semibold text-slate-500">Create active room types and rooms to see availability.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
