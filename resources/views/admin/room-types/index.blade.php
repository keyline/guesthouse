@extends('admin.layouts.app')

@section('title', 'Room Types')
@section('eyebrow', 'Room Inventory')
@section('page-title', 'Room Types')

@section('header-actions')
    <a href="{{ route('admin.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Rooms</a>
    <a href="{{ route('admin.room-types.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">+ Add Room Type</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.room-types.index') }}" class="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
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
            <div class="flex items-end gap-2">
                <button class="h-10 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Filter</button>
                <a href="{{ route('admin.room-types.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Reset</a>
            </div>
        </form>
    </section>

    <section class="mt-6 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($roomTypes as $roomType)
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $roomType->code }}</p>
                        <h2 class="mt-1 text-xl font-black">{{ $roomType->name }}</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $roomType->property->name }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ ucfirst($roomType->status) }}</span>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-2 text-center text-sm">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <span class="block font-black">{{ $roomType->formattedBasePrice() }}</span>
                        <span class="text-xs font-semibold text-slate-500">Price</span>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <span class="block font-black">{{ $roomType->max_adults + $roomType->max_children }}</span>
                        <span class="text-xs font-semibold text-slate-500">Guests</span>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <span class="block font-black">{{ $roomType->rooms_count }}</span>
                        <span class="text-xs font-semibold text-slate-500">Rooms</span>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('admin.room-types.show', $roomType) }}" class="inline-flex h-9 items-center rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-sky-700 transition">Open</a>
                    <a href="{{ route('admin.room-types.edit', $roomType) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700">Edit</a>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center lg:col-span-2 xl:col-span-3">
                <h2 class="text-xl font-black">No room types yet</h2>
                <p class="mt-2 text-sm text-slate-500">Create sellable categories like Standard, Deluxe, Suite, or Dormitory.</p>
                <a href="{{ route('admin.room-types.create') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Add Room Type</a>
            </div>
        @endforelse
    </section>

    <div class="mt-6">{{ $roomTypes->links() }}</div>
@endsection
