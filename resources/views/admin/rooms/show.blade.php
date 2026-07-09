@extends('admin.layouts.app')

@section('title', 'Room '.$room->room_number)
@section('eyebrow', 'Room Profile')
@section('page-title', 'Room '.$room->room_number)

@section('header-actions')
    <a href="{{ route('admin.rooms.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">All Rooms</a>
    <a href="{{ route('admin.rooms.edit', $room) }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">Edit</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $room->property->name }}</p>
            <h2 class="mt-1 text-2xl font-black">{{ $room->roomType->name }}</h2>
            <div class="mt-6 grid gap-3 md:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-500">Room</p>
                    <p class="mt-2 text-2xl font-black">{{ $room->room_number }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-500">Floor</p>
                    <p class="mt-2 text-2xl font-black">{{ $room->floor ?: '-' }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-500">Status</p>
                    <p class="mt-2 text-2xl font-black">{{ ucfirst($room->status) }}</p>
                </div>
            </div>

            @if ($room->notes)
                <div class="mt-6 rounded-lg border border-slate-200 p-4">
                    <h3 class="font-black">Internal Notes</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $room->notes }}</p>
                </div>
            @endif
        </article>

        <aside class="space-y-6">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Capacity & Rules</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Adults</dt><dd class="font-black">{{ $room->roomType->max_adults }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Children</dt><dd class="font-black">{{ $room->roomType->max_children }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Smoking</dt><dd class="font-black">{{ $room->is_smoking ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Accessible</dt><dd class="font-black">{{ $room->is_accessible ? 'Yes' : 'No' }}</dd></div>
                </dl>
            </section>

            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" class="rounded-lg border border-rose-200 bg-rose-50 p-5">
                @csrf
                @method('DELETE')
                <h2 class="text-lg font-black text-rose-950">Delete Room</h2>
                <p class="mt-2 text-sm font-semibold text-rose-700">Use blocked or maintenance for temporary unavailability.</p>
                <button class="mt-4 h-10 rounded-lg bg-rose-700 px-4 text-sm font-bold text-white" onclick="return confirm('Delete this room?')">Delete</button>
            </form>
        </aside>
    </section>
@endsection
