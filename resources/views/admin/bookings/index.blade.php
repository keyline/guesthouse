@extends('admin.layouts.app')

@section('title', 'Bookings')
@section('eyebrow', 'Booking Desk')
@section('page-title', 'Bookings')

@section('header-actions')
    <a href="{{ route('admin.availability.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Availability</a>
    <a href="{{ route('admin.bookings.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">+ New Booking</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="grid gap-3 lg:grid-cols-[1fr_1fr_1fr_1fr_auto]">
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
                <label for="status" class="text-sm font-bold text-slate-700">Status</label>
                <select id="status" name="status" class="mt-2 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="text-sm font-bold text-slate-700">From</label>
                <input id="from" name="from" type="date" value="{{ request('from') }}" class="mt-2 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div>
                <label for="to" class="text-sm font-bold text-slate-700">To</label>
                <input id="to" name="to" type="date" value="{{ request('to') }}" class="mt-2 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button class="h-10 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Filter</button>
                <a href="{{ route('admin.bookings.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Reset</a>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Booking</th>
                        <th class="px-5 py-3">Guest</th>
                        <th class="px-5 py-3">Property</th>
                        <th class="px-5 py-3">Room</th>
                        <th class="px-5 py-3">Dates</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bookings as $booking)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-black">{{ $booking->booking_number }}</td>
                            <td class="px-5 py-4">
                                <span class="block font-bold">{{ $booking->guest_name }}</span>
                                <span class="text-xs font-semibold text-slate-500">{{ $booking->guest_phone ?: $booking->guest_email }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $booking->property->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $booking->room?->room_number ?? 'Unassigned' }} / {{ $booking->roomType->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $booking->check_in_date->format('M j') }} - {{ $booking->check_out_date->format('M j, Y') }}</td>
                            <td class="px-5 py-4 font-bold">{{ $booking->formattedTotal() }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ str_replace('_', ' ', ucfirst($booking->status)) }}</span></td>
                            <td class="px-5 py-4">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="font-bold text-slate-900">Open</a>
                                    <a href="{{ route('admin.bookings.edit', $booking) }}" class="font-bold text-slate-600">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center">
                                <h2 class="text-xl font-black">No bookings yet</h2>
                                <p class="mt-2 text-sm font-semibold text-slate-500">Create a direct booking after properties, room types, and rooms are ready.</p>
                                <a href="{{ route('admin.bookings.create') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">New Booking</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $bookings->links() }}</div>
@endsection
