@extends('admin.layouts.app')

@section('title', 'Rooms')
@section('eyebrow', 'Room Inventory')
@section('page-title', 'Rooms')

@section('header-actions')
    <a href="{{ route('admin.room-types.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Room Types</a>
    <a href="{{ route('admin.rooms.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">+ Add Room</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.rooms.index') }}" class="grid gap-3 lg:grid-cols-[1fr_1fr_1fr_auto]">
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
                <label for="room_type_id" class="text-sm font-bold text-slate-700">Room type</label>
                <select id="room_type_id" name="room_type_id" class="mt-2 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">All room types</option>
                    @foreach ($roomTypes as $id => $name)
                        <option value="{{ $id }}" @selected((int) request('room_type_id') === $id)>{{ $name }}</option>
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
            <div class="flex items-end gap-2">
                <button class="h-10 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Filter</button>
                <a href="{{ route('admin.rooms.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Reset</a>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Room</th>
                        <th class="px-5 py-3">Property</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Floor</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Flags</th>
                        <th class="px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rooms as $room)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-black">{{ $room->room_number }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $room->property->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $room->roomType->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $room->floor ?: '-' }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ ucfirst($room->status) }}</span></td>
                            <td class="px-5 py-4 text-slate-600">{{ collect([$room->is_smoking ? 'Smoking' : null, $room->is_accessible ? 'Accessible' : null])->filter()->join(', ') ?: '-' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.rooms.show', $room) }}" class="font-bold text-slate-900">Open</a>
                                    <a href="{{ route('admin.rooms.edit', $room) }}" class="font-bold text-slate-600">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center">
                                <h2 class="text-xl font-black">No rooms yet</h2>
                                <p class="mt-2 text-sm font-semibold text-slate-500">Create room types first, then add physical rooms like 101, 102, and Suite A.</p>
                                <a href="{{ route('admin.rooms.create') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Add Room</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $rooms->links() }}</div>
@endsection
