<form method="POST" action="{{ route('admin.online-inventory.update') }}" data-inventory-form class="{{ $compact ?? false ? 'admin-card overflow-hidden' : '' }}">
    @csrf @method('PUT')
    <input type="hidden" name="property_id" value="{{ $inventory['property']->id }}">
    @if($returnOverview ?? false)<input type="hidden" name="return_mode" value="overview">@endif

    <section class="{{ $compact ?? false ? 'border-b border-slate-200 px-4 py-3' : 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm' }}">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-black text-slate-950">{{ $inventory['property']->name }}</h2>
                    <span class="text-xs font-semibold text-slate-500">{{ $inventory['property']->city }}</span>
                </div>
                <p class="mt-0.5 text-xs font-semibold text-slate-500">Click a room to toggle online sale. 🌐 online · 🛠 maintenance · ⛔ blocked · <span class="inline-block h-1.5 w-1.5 rounded-full bg-violet-500"></span> upcoming booking</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-9 items-center rounded-lg border border-sky-200 bg-sky-50 px-3 text-xs font-black text-sky-800"><span data-online-counter>{{ $inventory['onlineRooms'] }}</span>&nbsp;of {{ $inventory['totalRooms'] }} online</span>
                <button type="submit" class="h-9 rounded-lg bg-sky-600 px-4 text-xs font-black text-white hover:bg-sky-700">Save</button>
            </div>
        </div>
    </section>

    <div class="{{ $compact ?? false ? 'divide-y divide-slate-100' : '' }}">
        @forelse($inventory['roomGroups'] as $typeName => $group)
            <section class="{{ $compact ?? false ? '' : 'mt-3 rounded-lg border border-slate-200 bg-white shadow-sm' }}" data-inventory-group>
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-2">
                    <h3 class="text-xs font-black uppercase tracking-wide text-slate-800">{{ $typeName }} <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] normal-case text-slate-600"><span data-group-counter>{{ $group['online'] }}</span> of {{ $group['total'] }} online</span></h3>
                    <div class="flex gap-1.5"><button type="button" data-bulk="on" class="rounded border border-slate-300 px-2 py-1 text-[10px] font-bold">All on</button><button type="button" data-bulk="off" class="rounded border border-slate-300 px-2 py-1 text-[10px] font-bold">All off</button></div>
                </div>
                <div class="px-4 py-1" data-room-group>
                    @foreach($group['floors'] as $floorName => $floorRooms)
                        <div class="flex flex-wrap items-center gap-2 py-2">
                            <span class="w-24 shrink-0 text-[10px] font-black uppercase text-slate-400">{{ $floorName }}</span>
                            @foreach($floorRooms as $room)
                                @php($sellable = $room->status === \App\Models\Room::STATUS_AVAILABLE)
                                @php($hasUpcoming = ($inventory['upcomingBookedRoomIds'][$room->id] ?? 0) > 0)
                                <label class="{{ $sellable ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}" data-room-card title="Room {{ $room->room_number }}{{ $hasUpcoming ? ' · upcoming booking' : '' }}{{ $sellable ? '' : ' · '.$room->status }}">
                                    <input type="checkbox" name="room_ids[]" value="{{ $room->id }}" class="peer sr-only" data-room-toggle @checked($room->is_online_bookable) @disabled(!$sellable)>
                                    <span class="inline-flex h-8 items-center gap-1.5 rounded-lg border px-2.5 text-xs font-black {{ $sellable ? 'border-slate-200 bg-white text-slate-700 peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-800 peer-checked:ring-1 peer-checked:ring-sky-500' : 'border-slate-100 bg-slate-50 text-slate-400' }}">
                                        {{ $room->room_number }}
                                        @if(!$sellable)<span>{{ $room->status === \App\Models\Room::STATUS_MAINTENANCE ? '🛠' : '⛔' }}</span>@else<span class="{{ $room->is_online_bookable ? '' : 'hidden' }}" data-online-badge>🌐</span>@endif
                                        @if($hasUpcoming)<span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="p-4 text-sm font-semibold text-slate-500">No rooms configured for this property.</p>
        @endforelse
    </div>
</form>
