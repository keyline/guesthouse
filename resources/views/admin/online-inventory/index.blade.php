@extends('admin.layouts.app')

@section('title', 'Online Inventory')
@section('eyebrow', 'Distribution')
@section('page-title', 'Online Inventory')

@section('content')
    @if(session('status'))<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif

    @if($mode === 'overview')
        <section class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div><h2 class="text-sm font-black text-slate-950">Portfolio online availability</h2><p class="mt-0.5 text-xs font-semibold text-slate-500">Manage every accessible property here. Save each property after making changes.</p></div>
            <div class="flex gap-2"><span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-black text-slate-700">{{ $propertyInventories->count() }} properties</span><span class="rounded-lg bg-sky-50 px-3 py-2 text-xs font-black text-sky-800">{{ $propertyInventories->sum('onlineRooms') }} of {{ $propertyInventories->sum('totalRooms') }} rooms online</span></div>
        </section>
        <div class="grid gap-4">
            @forelse($propertyInventories as $inventory)
                @include('admin.online-inventory._property', ['inventory' => $inventory, 'compact' => true, 'returnOverview' => true])
            @empty
                <div class="admin-card p-8 text-center text-sm font-semibold text-slate-500">No properties available.</div>
            @endforelse
        </div>
    @else
        @include('admin.online-inventory._property', ['inventory' => ['property'=>$property,'roomGroups'=>$roomGroups,'totalRooms'=>$totalRooms,'onlineRooms'=>$onlineRooms,'upcomingBookedRoomIds'=>$upcomingBookedRoomIds]])
    @endif

    <script>
        document.querySelectorAll('[data-inventory-form]').forEach(form => {
            const toggles = [...form.querySelectorAll('[data-room-toggle]')];
            const refresh = () => {
                form.querySelector('[data-online-counter]').textContent = toggles.filter(t => t.checked && !t.disabled).length;
                form.querySelectorAll('[data-inventory-group]').forEach(group => {
                    const items = [...group.querySelectorAll('[data-room-toggle]')];
                    group.querySelector('[data-group-counter]').textContent = items.filter(t => t.checked && !t.disabled).length;
                });
            };
            toggles.forEach(toggle => toggle.addEventListener('change', () => { toggle.closest('[data-room-card]').querySelector('[data-online-badge]')?.classList.toggle('hidden', !toggle.checked); refresh(); }));
            form.querySelectorAll('[data-bulk]').forEach(button => button.addEventListener('click', () => {
                button.closest('[data-inventory-group]').querySelectorAll('[data-room-toggle]:not(:disabled)').forEach(toggle => { toggle.checked = button.dataset.bulk === 'on'; toggle.closest('[data-room-card]').querySelector('[data-online-badge]')?.classList.toggle('hidden', !toggle.checked); }); refresh();
            }));
        });
    </script>
@endsection
