@php
    $toneClass = [
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
    ][$stat['tone'] ?? 'blue'] ?? 'bg-slate-50 text-slate-700 ring-slate-100';
@endphp

<article class="admin-card p-3">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
            <p class="mt-2 text-2xl font-black tracking-tight text-slate-950">{{ $stat['value'] }}</p>
            <p class="mt-1 text-xs font-bold {{ ($stat['growth'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ ($stat['growth'] ?? 0) >= 0 ? '+' : '' }}{{ $stat['growth'] }}% this week
            </p>
        </div>
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg ring-1 {{ $toneClass }}">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="{{ $stat['iconPath'] }}"></path>
            </svg>
        </span>
    </div>
    <div class="mt-3 flex h-8 items-end gap-1">
        @foreach ($stat['sparkline'] as $height)
            <span class="flex-1 rounded-t bg-slate-200" style="height: {{ $height }}%"></span>
        @endforeach
    </div>
</article>
