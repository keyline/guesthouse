@extends('admin.layouts.app')

@section('title', 'Room Types')
@section('eyebrow', 'Room Inventory')
@section('page-title', 'Room Types')

@section('header-lead')
    @if (! empty($selectedPropertyName))
        <div class="flex items-center gap-2.5">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-600 text-base font-black text-white shadow-sm">{{ mb_strtoupper(mb_substr($selectedPropertyName, 0, 1)) }}</span>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Managing property</p>
                <p class="truncate text-base font-black leading-tight text-slate-900">{{ $selectedPropertyName }}</p>
            </div>
        </div>
    @endif
@endsection

@section('header-actions')
    <a href="{{ route('admin.rooms.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Rooms</a>
    <a href="{{ route('admin.room-types.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">+ Add Room Type</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($requireProperty)
        <section class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-sky-50 text-sky-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M12 9h.01M15 9h.01M9 13h.01M12 13h.01M15 13h.01M10 21v-4h4v4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-black text-slate-900">Choose a property to manage its room types</h3>
            <p class="mx-auto mt-1 max-w-md text-sm font-semibold text-slate-500">Room categories are configured per property — rooms, online availability and rates differ for each. Pick one to continue.</p>
            @if ($scopeProperties->isNotEmpty())
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    @foreach ($scopeProperties as $prop)
                        <form method="POST" action="{{ route('admin.property-context.update') }}">
                            @csrf
                            <input type="hidden" name="property_id" value="{{ $prop->id }}">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-black text-slate-700 transition hover:border-sky-400 hover:bg-sky-50 hover:text-sky-700">
                                <span class="h-2 w-2 rounded-full bg-sky-500"></span> {{ $prop->name }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
            <p class="mt-5 text-xs font-semibold text-slate-400">You can also switch properties from the selector at the top of the page.</p>
        </section>
    @else

    <section class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="room-type-toolbar">
            <div class="flex flex-wrap gap-2">
                <span class="rounded-lg bg-slate-100 px-3 py-2 text-[11px] font-black text-slate-700">{{ $roomTypes->total() }} categories</span>
                <span class="rounded-lg bg-sky-50 px-3 py-2 text-[11px] font-black text-sky-700">{{ $categoryStats->sum('online') }} online rooms</span>
                <span class="rounded-lg {{ $categoryStats->sum('attention') ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-3 py-2 text-[11px] font-black">{{ $categoryStats->sum('attention') }} need attention</span>
                <span class="rounded-lg bg-violet-50 px-3 py-2 text-[11px] font-black text-violet-700">{{ $selectedPropertyName ?: $contextPropertyCount.' properties' }}</span>
            </div>
            <form method="GET" action="{{ route('admin.room-types.index') }}" class="flex items-end gap-2">
            <div class="min-w-44">
                <label for="status" class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-400">Status</label>
                <select id="status" name="status" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-700">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="h-9 rounded-lg bg-sky-600 px-3 text-xs font-black text-white hover:bg-sky-700">Apply</button>
                <a href="{{ route('admin.room-types.index') }}" title="Clear filter" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-xs font-black text-slate-600">Clear</a>
            </div>
            </form>
        </div>
    </section>

    <style>.room-type-toolbar{display:flex;align-items:end;justify-content:space-between;gap:12px}.room-type-toolbar form>div:last-child{display:flex;gap:8px}@media(max-width:800px){.room-type-toolbar{align-items:stretch;flex-direction:column}.room-type-toolbar form{width:100%}.room-type-toolbar form>div:first-child{flex:1}}</style>

    <section class="mt-6 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($roomTypes as $roomType)
            @php($stats = $categoryStats[$roomType->id])
            @php($missingProperties = max(0, $contextPropertyCount - $stats['properties']))
            <article class="flex flex-col rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-mono text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $roomType->code }}</p>
                        <h2 class="mt-0.5 truncate text-base font-black text-slate-950">{{ $roomType->name }}</h2>
                        <p class="mt-0.5 text-[11px] font-semibold text-slate-500">Sleeps {{ $roomType->max_adults }} adults{{ $roomType->max_children ? ' + '.$roomType->max_children.' children' : '' }}</p>
                    </div>
                    <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $roomType->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $roomType->status }}</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5 text-[10px] font-bold text-slate-600"><span class="rounded bg-slate-100 px-2 py-1">{{ $roomType->is_pet_friendly ? '🐾 Pet friendly' : 'No pets' }}</span><span class="rounded bg-slate-100 px-2 py-1">{{ $roomType->extra_bed_available ? '🛏 '.$roomType->max_extra_beds.' extra bed'.($roomType->max_extra_beds > 1 ? 's' : '') : 'No extra bed' }}</span></div>
                <div class="room-type-metrics mt-3 rounded-lg border border-slate-200 bg-slate-50 text-center">
                    <div><strong class="block text-sm text-slate-900">{{ $stats['rooms'] }}</strong><span>Rooms</span></div>
                    <div><strong class="block text-sm text-sky-700">{{ $stats['online'] }}</strong><span>Online</span></div>
                    <div><strong class="block text-sm text-slate-900">{{ $stats['properties'] }}</strong><span>Properties</span></div>
                    <div><strong class="block text-sm text-violet-700">{{ $stats['rate_plans'] }}</strong><span>Rates</span></div>
                </div>
                @if($stats['attention'] || $missingProperties)
                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 rounded-lg bg-amber-50 px-2.5 py-2 text-[10px] font-bold text-amber-800">@if($stats['attention'])<span>⚠ {{ $stats['attention'] }} room{{ $stats['attention']>1?'s':'' }} blocked/maintenance</span>@endif @if($missingProperties)<span>○ Not configured at {{ $missingProperties }} propert{{ $missingProperties>1?'ies':'y' }}</span>@endif</div>
                @else
                    <div class="mt-2 rounded-lg bg-emerald-50 px-2.5 py-2 text-[10px] font-bold text-emerald-700">✓ Configured across the selected portfolio</div>
                @endif
                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                    <a href="{{ route('admin.room-types.show', $roomType) }}" class="text-xs font-black text-sky-700">View properties →</a>
                    <a href="{{ route('admin.room-types.edit', $roomType) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-[11px] font-black text-slate-600">Edit</a>
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
    <style>
        .room-type-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));overflow:hidden}
        .room-type-metrics>div{padding:8px 4px;border-right:1px solid #e2e8f0}
        .room-type-metrics>div:last-child{border-right:0}
        .room-type-metrics span{display:block;margin-top:1px;color:#94a3b8;font-size:8px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap}
    </style>
    @endif
@endsection
