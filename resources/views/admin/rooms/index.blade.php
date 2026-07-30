@extends('admin.layouts.app')

@section('title', 'Rooms')
@section('eyebrow', 'Room Inventory')
@section('page-title', 'Rooms')

@section('header-lead')
    @if ($selectedProperty)
        <div class="flex items-center gap-2.5">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-600 text-base font-black text-white shadow-sm">{{ mb_strtoupper(mb_substr($selectedProperty->name, 0, 1)) }}</span>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Managing property</p>
                <p class="truncate text-base font-black leading-tight text-slate-900">{{ $selectedProperty->name }}</p>
            </div>
        </div>
    @endif
@endsection

@section('header-actions')
    <a href="{{ route('admin.room-types.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Room Types</a>
    <a href="{{ route('admin.rooms.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">+ Add Room</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
        <form method="GET" action="{{ route('admin.rooms.index') }}" class="room-filter-toolbar">
            <div class="min-w-0">
                <label for="room_type_id" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Room category</label>
                <select id="room_type_id" name="room_type_id" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                    <option value="">All room types</option>
                    @foreach ($roomTypes as $id => $name)
                        <option value="{{ $id }}" @selected((int) request('room_type_id') === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label for="status" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Room status</label>
                <select id="status" name="status" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 self-end">
                <button class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-sky-600 px-4 text-xs font-black text-white transition hover:bg-sky-700">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    Apply
                </button>
                <a href="{{ route('admin.rooms.index') }}" title="Clear filters" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-black text-slate-600 transition hover:bg-slate-50">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </a>
            </div>
        </form>
    </section>

    <style>
        .room-filter-toolbar { display:grid; grid-template-columns:minmax(220px,1fr) minmax(180px,.72fr) auto; align-items:end; gap:10px; }
        @media (max-width: 760px) { .room-filter-toolbar { grid-template-columns:1fr; } .room-filter-toolbar > div:last-child { justify-self:stretch; } }
    </style>

    <div class="mt-4 grid gap-4">
        @forelse($roomGroups as $typeName => $categoryRooms)
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3">
                <div><h2 class="text-sm font-black text-slate-900">{{ $typeName }}</h2><p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $categoryRooms->count() }} {{ Str::plural('room', $categoryRooms->count()) }}</p></div>
                <a href="{{ route('admin.rooms.create', ['room_type_id' => $categoryRooms->first()?->room_type_id]) }}" class="text-xs font-black text-sky-700">+ Add room in this category</a>
            </header>
            <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Room</th>
                        @if($showProperty)<th class="px-5 py-3">Property</th>@endif
                        <th class="px-5 py-3">Floor</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Flags</th>
                        <th class="px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($categoryRooms as $room)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-black">{{ $room->room_number }}</td>
                            @if($showProperty)<td class="px-5 py-4 text-slate-600">{{ $room->property->name }}</td>@endif
                            <td class="px-5 py-4 text-slate-600">{{ $room->floor ?: '-' }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ ucfirst($room->status) }}</span></td>
                            <td class="px-5 py-4 text-slate-600">{{ collect([$room->is_smoking ? 'Smoking' : null, $room->is_accessible ? 'Accessible' : null])->filter()->join(', ') ?: '-' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.rooms.edit', $room) }}" class="font-bold text-slate-900">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </section>
        @empty
            <section class="admin-card p-10 text-center"><h2 class="text-xl font-black">No rooms found</h2><p class="mt-2 text-sm font-semibold text-slate-500">Adjust the filters or add the first room for the property selected in the header.</p><a href="{{ route('admin.rooms.create') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white">+ Add Room</a></section>
        @endforelse
    </div>

    <div class="mt-6">{{ $rooms->links() }}</div>
@endsection
