@extends('admin.layouts.app')

@section('title', $roomType->name)
@section('eyebrow', $properties->isNotEmpty() ? 'Room Type Profile · '.$properties->first()->name : 'Room Type Profile')
@section('page-title', $roomType->name)

@section('header-lead')
    @if ($properties->isNotEmpty())
        <div class="flex items-center gap-2.5">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-600 text-base font-black text-white shadow-sm">{{ mb_strtoupper(mb_substr($properties->first()->name, 0, 1)) }}</span>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Managing property</p>
                <p class="truncate text-base font-black leading-tight text-slate-900">{{ $properties->first()->name }}</p>
            </div>
        </div>
    @endif
@endsection

@section('header-actions')
    <a href="{{ route('admin.room-types.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Room Types
    </a>
    @if($roomType->status === \App\Models\RoomType::STATUS_ACTIVE)
    <a href="{{ route('admin.rooms.create', ['room_type_id' => $roomType->id]) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Add Room</a>
    @endif
    <a href="{{ route('admin.room-types.edit', $roomType) }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">Edit</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif
    @error('room_type')
        <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ $message }}</div>
    @enderror

    <div class="grid gap-4">
        <article class="rounded-lg border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2"><h2 class="text-xl font-black text-slate-950">{{ $roomType->name }}</h2><span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[10px] font-bold text-slate-500">{{ $roomType->code }}</span><span class="rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $roomType->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ $roomType->status }}</span></div>
                    <p class="mt-1 text-xs font-semibold text-slate-500">
                        @if ($properties->isNotEmpty())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-black text-sky-700"><span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>{{ $properties->first()->name }}{{ ($city = collect([$properties->first()->city, $properties->first()->state])->filter()->join(', ')) ? ' · '.$city : '' }}</span>
                        @else
                            {{ $roomType->description ?: 'Global room category used across properties.' }}
                        @endif
                    </p>
                </div>
                <dl class="flex flex-wrap gap-2">
                    <div class="min-w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center"><dt class="text-[9px] font-black uppercase tracking-wide text-slate-400">Adults</dt><dd class="mt-0.5 text-sm font-black">{{ $roomType->max_adults }}</dd></div>
                    <div class="min-w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center"><dt class="text-[9px] font-black uppercase tracking-wide text-slate-400">Children</dt><dd class="mt-0.5 text-sm font-black">{{ $roomType->max_children }}</dd></div>
                    <div class="min-w-24 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center"><dt class="text-[9px] font-black uppercase tracking-wide text-slate-400">Extra bed</dt><dd class="mt-0.5 text-xs font-black">{{ $roomType->extra_bed_available ? $roomType->max_extra_beds.' · '.($roomType->extra_bed_charge_minor ? '₹'.number_format($roomType->extra_bed_charge_minor / 100) : 'Free') : 'No' }}</dd></div>
                    <div class="min-w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center"><dt class="text-[9px] font-black uppercase tracking-wide text-slate-400">Pets</dt><dd class="mt-0.5 text-sm font-black">{{ $roomType->is_pet_friendly ? '🐾 Yes' : 'No' }}</dd></div>
                    <div class="min-w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center"><dt class="text-[9px] font-black uppercase tracking-wide text-slate-400">Rooms</dt><dd class="mt-0.5 text-sm font-black">{{ $requireProperty ? $roomType->rooms->count() : $roomType->rooms->whereIn('property_id', $properties->pluck('id'))->count() }}</dd></div>
                </dl>
            </div>
        </article>

        <section>
            <div class="mb-2 flex flex-wrap items-end justify-between gap-2"><div><h2 class="text-sm font-black text-slate-900">Rooms &amp; availability</h2><p class="mt-0.5 text-[11px] font-semibold text-slate-500">Room coverage and online-sale readiness for this category{{ $properties->isNotEmpty() ? ' at '.$properties->first()->name : ' at the selected property' }}.</p></div><div class="flex gap-2 text-[10px] font-bold text-slate-500"><span>● Available</span><span class="text-sky-700">● Online</span><span class="text-amber-700">● Attention</span></div></div>
            @if ($requireProperty)
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-sky-50 text-sky-600">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M12 9h.01M15 9h.01M9 13h.01M12 13h.01M15 13h.01M10 21v-4h4v4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">Select a property to manage this category</h3>
                    <p class="mx-auto mt-1 max-w-md text-sm font-semibold text-slate-500">Rooms, online availability and amenities for “{{ $roomType->name }}” are configured per property. Pick one to continue.</p>
                    @if ($scopeProperties->isNotEmpty())
                        <div class="mt-6 flex flex-wrap justify-center gap-2">
                            @foreach ($scopeProperties as $prop)
                                <form method="POST" action="{{ route('admin.property-context.update') }}">
                                    @csrf
                                    <input type="hidden" name="property_id" value="{{ $prop->id }}">
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-black text-slate-700 transition hover:border-sky-400 hover:bg-sky-50 hover:text-sky-700"><span class="h-2 w-2 rounded-full bg-sky-500"></span> {{ $prop->name }}</button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                    <p class="mt-5 text-xs font-semibold text-slate-400">You can also switch properties from the selector at the top of the page.</p>
                </div>
            @else
            <div class="grid gap-3">
                @foreach($properties as $property)
                    @php
                        $propertyRooms = $roomType->rooms->where('property_id', $property->id)->sortBy('room_number', SORT_NATURAL);
                        $configuration = $roomType->propertyConfigurations->firstWhere('property_id', $property->id);
                        $selectedAmenities = $configuration?->amenities->pluck('id')->all() ?? [];
                        $onlineRooms = $propertyRooms->filter(fn($room) => $room->status === \App\Models\Room::STATUS_AVAILABLE && $room->is_online_bookable)->count();
                        $attentionRooms = $propertyRooms->whereIn('status', [\App\Models\Room::STATUS_MAINTENANCE, \App\Models\Room::STATUS_BLOCKED])->count();
                    @endphp
                    {{-- Card 1 · compact, collapsible category configuration --}}
                    <article id="property-config-{{ $property->id }}" class="scroll-mt-28 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-black text-slate-800">
                                <span class="flex items-center gap-2">⚙️ Configure category at {{ $property->name }}</span>
                                <span class="flex items-center gap-2 text-[11px] font-bold {{ $configuration ? 'text-slate-500' : 'text-amber-600' }}">{{ $configuration ? $configuration->amenities->count().' amenities' : 'Setup required' }} <svg class="h-4 w-4 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span>
                            </summary>
                            <form method="POST" action="{{ route('admin.room-types.properties.update',[$roomType,$property]) }}" class="border-t border-slate-100 bg-slate-50/70 p-4">
                                @csrf @method('PUT')
                                <div class="grid gap-3 md:grid-cols-4">
                                    <div><label class="text-[9px] font-black uppercase text-slate-500">Status</label><select name="status" class="mt-1 h-9 w-full rounded-lg border border-slate-300 bg-white px-2 text-xs"><option value="active" @selected(($configuration?->status ?? 'active')==='active')>Active</option><option value="inactive" @selected($configuration?->status==='inactive')>Inactive</option></select></div>
                                    <div><label class="text-[9px] font-black uppercase text-slate-500">Display name</label><input name="display_name" value="{{ $configuration?->display_name }}" placeholder="{{ $roomType->name }}" class="mt-1 h-9 w-full rounded-lg border border-slate-300 px-2 text-xs"></div>
                                    <div><label class="text-[9px] font-black uppercase text-slate-500">Adults</label><input name="max_adults" type="number" min="1" max="20" value="{{ $configuration?->max_adults ?? $roomType->max_adults }}" class="mt-1 h-9 w-full rounded-lg border border-slate-300 px-2 text-xs"></div>
                                    <div><label class="text-[9px] font-black uppercase text-slate-500">Children</label><input name="max_children" type="number" min="0" max="20" value="{{ $configuration?->max_children ?? $roomType->max_children }}" class="mt-1 h-9 w-full rounded-lg border border-slate-300 px-2 text-xs"></div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <label class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold"><input type="checkbox" name="is_pet_friendly" value="1" @checked($configuration?->is_pet_friendly ?? $roomType->is_pet_friendly)> 🐾 Pet friendly</label>
                                    <label class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold"><input type="checkbox" name="extra_bed_available" value="1" @checked($configuration?->extra_bed_available ?? $roomType->extra_bed_available)> 🛏 Extra bed</label>
                                    <input name="max_extra_beds" type="number" min="0" max="5" value="{{ $configuration?->max_extra_beds ?? $roomType->max_extra_beds }}" title="Maximum extra beds" class="h-9 w-24 rounded-lg border border-slate-300 px-2 text-xs" placeholder="Max beds">
                                    <input name="extra_bed_charge" type="number" min="0" step="0.01" value="{{ number_format(($configuration?->extra_bed_charge_minor ?? $roomType->extra_bed_charge_minor) / 100,2,'.','') }}" title="Extra bed charge" class="h-9 w-28 rounded-lg border border-slate-300 px-2 text-xs" placeholder="₹ Charge">
                                    <select name="extra_bed_charge_basis" title="Charge basis" class="h-9 rounded-lg border border-slate-300 bg-white px-2 text-xs"><option value="per_night" @selected(($configuration?->extra_bed_charge_basis ?? 'per_night')==='per_night')>Per night</option><option value="per_stay" @selected($configuration?->extra_bed_charge_basis==='per_stay')>Per stay</option></select>
                                </div>
                                <div class="mt-3"><p class="text-[9px] font-black uppercase tracking-wide text-slate-500">In-room amenities promised for every room in this category</p><div class="mt-2 flex max-h-36 flex-wrap gap-1.5 overflow-y-auto">
                                    @forelse($roomAmenities as $amenity)<label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[10px] font-bold text-slate-700"><input type="checkbox" name="amenities[]" value="{{ $amenity->id }}" @checked(in_array($amenity->id,$selectedAmenities,true))> {{ $amenity->name }}</label>@empty<p class="text-xs text-slate-500">Create room-category amenities from the Amenities master first.</p>@endforelse
                                </div></div>
                                <div class="mt-3 flex items-end gap-3"><div class="flex-1"><label class="text-[9px] font-black uppercase text-slate-500">Property-specific description</label><input name="description" value="{{ $configuration?->description }}" class="mt-1 h-9 w-full rounded-lg border border-slate-300 px-2 text-xs" placeholder="Optional guest-facing description"></div><button class="h-9 rounded-lg bg-sky-600 px-4 text-xs font-black text-white">Save configuration</button></div>
                            </form>
                        </details>
                    </article>

                    {{-- Card 2 · rooms in this category at the property --}}
                    <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h3 class="text-sm font-black text-slate-900">Rooms in this category</h3>
                            <div class="flex shrink-0 items-center gap-1.5"><span class="rounded-full bg-white px-2 py-1 text-[10px] font-black text-slate-600 ring-1 ring-slate-200">{{ $propertyRooms->count() }} rooms</span><span class="rounded-full bg-sky-50 px-2 py-1 text-[10px] font-black text-sky-700 ring-1 ring-sky-100">{{ $onlineRooms }} online</span>@if($attentionRooms)<span class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-black text-amber-700 ring-1 ring-amber-100">{{ $attentionRooms }} attention</span>@endif</div>
                        </header>
                        @if($propertyRooms->isNotEmpty())
                            <div class="space-y-4 p-4">
                                @foreach($propertyRooms->groupBy(fn($r) => $r->floor ?: 'No floor') as $floor => $floorRooms)
                                    <div>
                                        <p class="mb-2 flex items-center gap-2 text-[10px] font-black uppercase tracking-wider text-slate-400"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4 21V10l8-6 8 6v11"/></svg>{{ $floor }} <span class="font-bold normal-case text-slate-300">· {{ $floorRooms->count() }} {{ Str::plural('room', $floorRooms->count()) }}</span></p>
                                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                            @foreach($floorRooms as $room)
                                                @php($isAvail = $room->status === \App\Models\Room::STATUS_AVAILABLE)
                                                <div class="group rounded-xl border border-slate-200 bg-white p-3 transition hover:border-sky-300 hover:shadow-sm">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="min-w-0">
                                                            <p class="truncate text-base font-black text-slate-900">{{ $room->room_number }}</p>
                                                            <p class="text-[10px] font-semibold text-slate-400">{{ $room->floor ?: 'No floor' }}</p>
                                                        </div>
                                                        <button type="button" data-room-edit
                                                            data-action="{{ route('admin.rooms.quick-update', $room) }}"
                                                            data-number="{{ $room->room_number }}"
                                                            data-floor="{{ $room->floor }}"
                                                            data-status="{{ $room->status }}"
                                                            data-smoking="{{ $room->is_smoking ? 1 : 0 }}"
                                                            data-accessible="{{ $room->is_accessible ? 1 : 0 }}"
                                                            class="shrink-0 rounded-lg border border-slate-200 px-2.5 py-1 text-[10px] font-black text-sky-700 transition hover:border-sky-300 hover:bg-sky-50">Edit</button>
                                                    </div>
                                                    <div class="mt-2.5 flex flex-wrap items-center gap-1">
                                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-black {{ $isAvail ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ ucfirst($room->status) }}</span>
                                                        @if($isAvail)
                                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-black {{ $room->is_online_bookable ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-500' }}">{{ $room->is_online_bookable ? '🌐 Online' : 'Offline' }}</span>
                                                        @endif
                                                        @if($room->is_smoking)<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">🚬 Smoking</span>@endif
                                                        @if($room->is_accessible)<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">♿ Accessible</span>@endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex items-center justify-between gap-3 px-4 py-6"><div><p class="text-xs font-bold text-slate-600">No rooms yet</p><p class="mt-0.5 text-[10px] font-semibold text-slate-400">No {{ $roomType->name }} rooms at {{ $property->name }}.</p></div>@if($roomType->status === 'active')<a href="{{ route('admin.rooms.create',['room_type_id'=>$roomType->id,'property_id'=>$property->id]) }}" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-[11px] font-black text-sky-700">+ Add first room</a>@endif</div>
                        @endif
                        @if($propertyRooms->isNotEmpty() && $roomType->status === 'active')<footer class="border-t border-slate-100 px-4 py-2 text-right"><a href="{{ route('admin.rooms.create',['room_type_id'=>$roomType->id,'property_id'=>$property->id]) }}" class="text-[10px] font-black text-sky-700">+ Add another room</a></footer>@endif
                    </article>
                @endforeach
            </div>
            @endif
        </section>

        <dialog id="roomEditModal" class="w-full max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/50">
            <form method="POST" data-room-form class="p-5">
                @csrf
                @method('PATCH')
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-black text-slate-900">Edit room <span data-room-title class="text-sky-700"></span></h3>
                    <button type="button" data-room-close class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100">✕</button>
                </div>
                <div class="grid gap-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="text-[10px] font-black uppercase text-slate-500">Room number</label><input name="room_number" data-f-number required class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-500">Floor</label><select name="floor" data-f-floor class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm"><option value="">Select floor</option>@foreach(\App\Support\FloorOptions::all() as $floor)<option value="{{ $floor }}">{{ $floor }}</option>@endforeach</select></div>
                    </div>
                    <div><label class="text-[10px] font-black uppercase text-slate-500">Status</label><select name="status" data-f-status class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm"><option value="available">Available</option><option value="maintenance">Maintenance</option><option value="blocked">Blocked</option></select></div>
                    <div class="flex gap-2">
                        <label class="inline-flex h-10 flex-1 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-bold"><input type="checkbox" name="is_smoking" value="1" data-f-smoking> 🚬 Smoking</label>
                        <label class="inline-flex h-10 flex-1 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-bold"><input type="checkbox" name="is_accessible" value="1" data-f-accessible> ♿ Accessible</label>
                    </div>
                    <p class="text-[10px] font-semibold text-slate-400">Online availability is managed under Online Inventory.</p>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" data-room-close class="h-10 rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="h-10 rounded-lg bg-sky-600 px-5 text-sm font-black text-white hover:bg-sky-700">Save changes</button>
                </div>
            </form>
        </dialog>
        <script>
        (() => {
            const modal = document.getElementById('roomEditModal');
            if (!modal) return;
            const form = modal.querySelector('[data-room-form]');
            const q = (sel) => form.querySelector(sel);
            document.querySelectorAll('[data-room-edit]').forEach((btn) => btn.addEventListener('click', () => {
                form.action = btn.dataset.action;
                modal.querySelector('[data-room-title]').textContent = btn.dataset.number || '';
                q('[data-f-number]').value = btn.dataset.number || '';
                const floorSel = q('[data-f-floor]');
                const floor = btn.dataset.floor || '';
                if (floor && ![...floorSel.options].some((o) => o.value === floor)) {
                    floorSel.add(new Option(floor + ' (current)', floor));
                }
                floorSel.value = floor;
                q('[data-f-status]').value = btn.dataset.status || 'available';
                q('[data-f-smoking]').checked = btn.dataset.smoking === '1';
                q('[data-f-accessible]').checked = btn.dataset.accessible === '1';
                modal.showModal();
            }));
            modal.querySelectorAll('[data-room-close]').forEach((b) => b.addEventListener('click', () => modal.close()));
            modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });
        })();
        </script>

        <section class="rounded-lg border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="text-sm font-black text-slate-900">Category controls</h2><p class="mt-0.5 text-[11px] font-semibold text-slate-500">Prefer deactivation when this category has operational history.</p></div><div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.room-types.toggle-status',$roomType) }}">@csrf<button class="h-9 rounded-lg px-4 text-xs font-black text-white {{ $roomType->status === 'active' ? 'bg-amber-600' : 'bg-emerald-700' }}" onclick="return confirm('{{ $roomType->status === 'active' ? 'Deactivate this category? It will be hidden from new inventory.' : 'Activate this category?' }}')">{{ $roomType->status === 'active' ? 'Deactivate category' : 'Activate category' }}</button></form>
                <form method="POST" action="{{ route('admin.room-types.destroy',$roomType) }}">@csrf @method('DELETE')<button class="h-9 rounded-lg border border-rose-300 bg-white px-4 text-xs font-black text-rose-700 disabled:cursor-not-allowed disabled:opacity-40" @disabled($roomType->rooms->isNotEmpty() || $roomType->bookings_count > 0) onclick="return confirm('Permanently delete this room category?')">Delete permanently</button></form>
            </div></div>
            @if($roomType->rooms->isNotEmpty() || $roomType->bookings_count > 0)<p class="mt-2 text-right text-[10px] font-semibold text-slate-400">Permanent deletion is unavailable because rooms or booking history exist.</p>@endif
        </section>
    </div>
@endsection
