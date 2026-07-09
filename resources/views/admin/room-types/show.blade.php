@extends('admin.layouts.app')

@section('title', $roomType->name)
@section('eyebrow', 'Room Type Profile')
@section('page-title', $roomType->name)

@section('header-actions')
    <a href="{{ route('admin.rooms.create', ['room_type_id' => $roomType->id]) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Add Room</a>
    <a href="{{ route('admin.room-types.edit', $roomType) }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">Edit</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif
    @error('room_type')
        <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ $message }}</div>
    @enderror

    <section class="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $roomType->code }}</p>
            <h2 class="mt-1 text-2xl font-black">{{ $roomType->name }}</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">Global room type master used by rooms across properties.</p>
            @if ($roomType->description)
                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $roomType->description }}</p>
            @endif

            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Room</th>
                            <th class="px-4 py-3">Property</th>
                            <th class="px-4 py-3">Floor</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Flags</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($roomType->rooms as $room)
                            <tr>
                                <td class="px-4 py-3 font-bold"><a href="{{ route('admin.rooms.edit', $room) }}">{{ $room->room_number }}</a></td>
                                <td class="px-4 py-3 text-slate-600">{{ $room->property->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $room->floor ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ ucfirst($room->status) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ collect([$room->is_smoking ? 'Smoking' : null, $room->is_accessible ? 'Accessible' : null])->filter()->join(', ') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center font-semibold text-slate-500">No rooms assigned yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="space-y-6">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Capacity Setup</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Status</dt><dd class="font-black">{{ ucfirst($roomType->status) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Adults</dt><dd class="font-black">{{ $roomType->max_adults }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Children</dt><dd class="font-black">{{ $roomType->max_children }}</dd></div>
                </dl>
            </section>

            <form method="POST" action="{{ route('admin.room-types.destroy', $roomType) }}" class="rounded-lg border border-rose-200 bg-rose-50 p-5">
                @csrf
                @method('DELETE')
                <h2 class="text-lg font-black text-rose-950">Delete Room Type</h2>
                <p class="mt-2 text-sm font-semibold text-rose-700">Allowed only when no rooms are assigned.</p>
                <button class="mt-4 h-10 rounded-lg bg-rose-700 px-4 text-sm font-bold text-white" onclick="return confirm('Delete this room type?')">Delete</button>
            </form>
        </aside>
    </section>
@endsection
