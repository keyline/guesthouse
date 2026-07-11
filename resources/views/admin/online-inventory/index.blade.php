@extends('admin.layouts.app')

@section('title', 'Online Inventory')
@section('eyebrow', 'Distribution')
@section('page-title', 'Online Inventory')

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($mode === 'overview')
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-black text-slate-950">Choose a property to manage its online rooms</h2>
            <p class="mt-1 text-xs font-semibold text-slate-500">Rooms marked online are sellable through the booking website. Everything else stays reserved for walk-in and phone bookings.</p>
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($propertySummaries as $summary)
                <article class="admin-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-black text-slate-950">{{ $summary['property']->name }}</h3>
                            <p class="mt-0.5 truncate text-xs font-semibold text-slate-500">{{ $summary['property']->city }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-black text-sky-700 ring-1 ring-sky-100">
                            {{ $summary['online'] }} / {{ $summary['total'] }} online
                        </span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-sky-500" style="width: {{ $summary['total'] > 0 ? round($summary['online'] / $summary['total'] * 100) : 0 }}%"></div>
                    </div>
                    <a href="{{ route('admin.online-inventory.index', ['property_id' => $summary['property']->id]) }}"
                       class="mt-4 inline-flex h-9 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white transition hover:bg-sky-700">
                        Manage rooms
                    </a>
                </article>
            @empty
                <p class="text-sm font-semibold text-slate-500">No properties available.</p>
            @endforelse
        </div>
    @else
        <form method="POST" action="{{ route('admin.online-inventory.update') }}" id="onlineInventoryForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="property_id" value="{{ $property->id }}">

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-slate-950">{{ $property->name }}</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">
                            Toggle the rooms guests can book online. Rooms under maintenance or blocked are never sold online.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 items-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 text-sm font-black text-sky-800">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm7.93 9h-3.98a15.6 15.6 0 00-1.13-5.3A8.02 8.02 0 0119.93 11zM12 4.06c.9 1.28 1.63 3.35 1.93 6.94h-3.86c.3-3.59 1.03-5.66 1.93-6.94zM9.18 5.7A15.6 15.6 0 008.05 11H4.07a8.02 8.02 0 015.11-5.3zM4.07 13h3.98c.14 1.96.53 3.78 1.13 5.3A8.02 8.02 0 014.07 13zM12 19.94c-.9-1.28-1.63-3.35-1.93-6.94h3.86c-.3 3.59-1.03 5.66-1.93 6.94zm2.82-1.64c.6-1.52.99-3.34 1.13-5.3h3.98a8.02 8.02 0 01-5.11 5.3z"/></svg>
                            <span id="onlineCounter">{{ $onlineRooms }}</span>&nbsp;of {{ $totalRooms }} rooms online
                        </span>
                        <button type="submit" class="h-10 rounded-lg bg-sky-600 px-5 text-sm font-black text-white transition hover:bg-sky-700">Save changes</button>
                    </div>
                </div>
            </section>

            @forelse ($roomGroups as $typeName => $group)
                <section class="mt-4 rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                        <h3 class="text-sm font-black uppercase tracking-wide text-slate-800">
                            {{ $typeName }}
                            <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-black normal-case text-slate-600">
                                <span data-group-counter>{{ $group['online'] }}</span> of {{ $group['total'] }} online
                            </span>
                        </h3>
                        <div class="flex gap-2">
                            <button type="button" data-bulk="on" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50">All on</button>
                            <button type="button" data-bulk="off" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50">All off</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6" data-room-group>
                        @foreach ($group['rooms'] as $room)
                            @php
                                $sellable = $room->status === \App\Models\Room::STATUS_AVAILABLE;
                                $hasUpcoming = ($upcomingBookedRoomIds[$room->id] ?? 0) > 0;
                            @endphp
                            <label class="relative block cursor-pointer rounded-xl border p-3 transition {{ $sellable ? 'border-slate-200 bg-white hover:border-sky-300' : 'cursor-not-allowed border-slate-100 bg-slate-50 opacity-70' }}"
                                   data-room-card>
                                <input type="checkbox"
                                       name="room_ids[]"
                                       value="{{ $room->id }}"
                                       class="peer sr-only"
                                       data-room-toggle
                                       @checked($room->is_online_bookable)
                                       @disabled(! $sellable)>
                                <span class="pointer-events-none absolute inset-0 rounded-xl ring-2 ring-transparent transition peer-checked:ring-sky-500"></span>
                                <span class="flex items-start justify-between gap-2">
                                    <span class="text-base font-black text-slate-950">{{ $room->room_number }}</span>
                                    @if (! $sellable)
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-black uppercase text-amber-800">{{ $room->status === \App\Models\Room::STATUS_MAINTENANCE ? 'Maint.' : 'Blocked' }}</span>
                                    @else
                                        <span class="{{ $room->is_online_bookable ? '' : 'hidden' }} rounded bg-sky-100 px-1.5 py-0.5 text-[10px] font-black uppercase text-sky-700" data-online-badge>Online</span>
                                    @endif
                                </span>
                                <span class="mt-1 block truncate text-[11px] font-semibold text-slate-500">
                                    {{ $room->floor ?: '—' }}
                                    @if ($room->is_accessible) · ♿ @endif
                                    @if ($room->is_smoking) · 🚬 @endif
                                </span>
                                @if ($hasUpcoming)
                                    <span class="mt-1 block text-[10px] font-bold text-violet-600" title="This room has upcoming bookings">● Upcoming booking</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="mt-4 rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <p class="text-sm font-bold text-slate-700">This property has no rooms yet.</p>
                    <a href="{{ route('admin.rooms.create') }}" class="mt-3 inline-flex h-9 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white hover:bg-sky-700">+ Add Room</a>
                </section>
            @endforelse
        </form>

        <script>
            (() => {
                const counter = document.getElementById('onlineCounter');
                const toggles = [...document.querySelectorAll('[data-room-toggle]')];

                function refresh() {
                    counter.textContent = toggles.filter((toggle) => toggle.checked && !toggle.disabled).length;

                    document.querySelectorAll('[data-room-group]').forEach((groupEl) => {
                        const groupToggles = [...groupEl.querySelectorAll('[data-room-toggle]')];
                        const groupCounter = groupEl.closest('section').querySelector('[data-group-counter]');
                        if (groupCounter) {
                            groupCounter.textContent = groupToggles.filter((toggle) => toggle.checked && !toggle.disabled).length;
                        }
                    });
                }

                toggles.forEach((toggle) => toggle.addEventListener('change', () => {
                    const badge = toggle.closest('[data-room-card]').querySelector('[data-online-badge]');
                    badge?.classList.toggle('hidden', !toggle.checked);
                    refresh();
                }));

                document.querySelectorAll('[data-bulk]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const groupEl = button.closest('section').querySelector('[data-room-group]');
                        groupEl.querySelectorAll('[data-room-toggle]:not(:disabled)').forEach((toggle) => {
                            toggle.checked = button.dataset.bulk === 'on';
                            const badge = toggle.closest('[data-room-card]').querySelector('[data-online-badge]');
                            badge?.classList.toggle('hidden', !toggle.checked);
                        });
                        refresh();
                    });
                });
            })();
        </script>
    @endif
@endsection
