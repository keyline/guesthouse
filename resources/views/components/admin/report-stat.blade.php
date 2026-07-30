@props(['label', 'value', 'tone' => 'slate'])
@php
    $styles = [
        'slate' => 'border-slate-200 bg-white text-slate-900',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-900',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-900',
        'violet' => 'border-violet-200 bg-violet-50 text-violet-900',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-900',
    ];
@endphp
<article {{ $attributes->class(['rounded-xl border px-3 py-2.5 shadow-sm', $styles[$tone] ?? $styles['slate']]) }}>
    <p class="text-[9px] font-black uppercase tracking-[0.14em] opacity-60">{{ $label }}</p>
    <p class="mt-1 truncate text-lg font-black" title="{{ $value }}">{{ $value }}</p>
</article>
