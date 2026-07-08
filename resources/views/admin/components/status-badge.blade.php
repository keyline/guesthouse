@php
    $tone = [
        'confirmed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'checked in' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'arriving' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'payment due' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'no show' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ][strtolower($status)] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
@endphp

<span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-black ring-1 {{ $tone }}">{{ $status }}</span>
