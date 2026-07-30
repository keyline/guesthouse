@extends('admin.layouts.app')

@section('title', 'Rate Calendar')
@section('eyebrow', 'Revenue')
@section('page-title', 'Rate Calendar')

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.rate-calendar.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="property_id" class="text-sm font-bold text-slate-700">Property</label>
                <select id="property_id" name="property_id" onchange="this.form.submit()" class="mt-2 h-10 w-64 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    @foreach ($properties as $option)
                        <option value="{{ $option->id }}" @selected($property && $property->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="start" class="text-sm font-bold text-slate-700">From</label>
                <input id="start" type="date" name="start" value="{{ $start->toDateString() }}" onchange="this.form.submit()" class="mt-2 h-10 rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <a href="{{ route('admin.rate-calendar.index', ['property_id' => $property?->id, 'start' => $start->subDays(14)->toDateString()]) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700">‹ Prev 14</a>
            <a href="{{ route('admin.rate-calendar.index', ['property_id' => $property?->id, 'start' => $start->addDays(14)->toDateString()]) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700">Next 14 ›</a>
        </form>
    </section>

    @if (! $property)
        <section class="mt-4 rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-bold text-slate-700">No property available.</p>
        </section>
    @elseif ($ratePlans->isEmpty())
        <section class="mt-4 rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-bold text-slate-700">No rate plans exist for {{ $property->name }} yet.</p>
            <p class="mt-1 text-xs font-semibold text-slate-500">Create a Standard Rate for its room types first — then refine per-night prices here.</p>
            <a href="{{ route('admin.rate-plans.index', ['property_id' => $property->id]) }}" class="mt-4 inline-flex h-10 items-center rounded-lg bg-sky-600 px-5 text-sm font-black text-white transition hover:bg-sky-700">Set up pricing</a>
        </section>
    @else
        <form method="POST" action="{{ route('admin.rate-calendar.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="property_id" value="{{ $property->id }}">
            <input type="hidden" name="start" value="{{ $start->toDateString() }}">

            <section class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-950">{{ $property->name }} — {{ $start->format('d M') }} to {{ $start->addDays(13)->format('d M Y') }}</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Each box is the price for one night — click a pill to toggle, then save.</p>
                        <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-semibold text-slate-500">
                            <span><span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black text-emerald-700 ring-1 ring-emerald-200">Selling</span> / <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black text-rose-700 ring-1 ring-rose-300">🚫 Blocked</span> = whole room type</span>
                            <span><span class="rounded-full px-2 py-0.5 text-[10px] font-black text-slate-400 ring-1 ring-slate-200">Open</span> / <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black text-rose-700 ring-1 ring-rose-300">✕ Closed</span> = that plan only</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span data-qf-pending hidden class="animate-pulse rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800 ring-1 ring-amber-300"></span>
                        <button type="submit" class="h-10 rounded-lg bg-sky-600 px-5 text-sm font-black text-white transition hover:bg-sky-700">Save changes</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-500">
                                <th class="sticky left-0 bg-slate-50 px-4 py-2 text-left">Rate plan</th>
                                @foreach ($dates as $date)
                                    <th class="px-1 py-2 text-center {{ $date->isWeekend() ? 'text-sky-600' : '' }}">
                                        {{ $date->format('D') }}<br>{{ $date->format('d/m') }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($planGroups as $typeName => $plans)
                                @php $roomTypeId = $plans->first()->room_type_id; @endphp
                                <tr class="border-t border-slate-200 bg-slate-100/60">
                                    <td class="sticky left-0 bg-slate-100 px-4 py-2 text-xs font-black uppercase tracking-wide text-slate-700">{{ $typeName }}</td>
                                    @foreach ($dates as $date)
                                        @php $row = $inventory->get($roomTypeId.'|'.$date->toDateString()); @endphp
                                        <td class="px-1 py-1.5 text-center">
                                            <span class="block text-[10px] font-bold {{ $row && $row->available() === 0 ? 'text-rose-600' : 'text-slate-500' }}"
                                                  title="{{ $row ? $row->available().' of '.$row->total_rooms.' rooms still free this night' : 'No inventory data yet' }}">
                                                {{ $row ? $row->available().' free' : '—' }}
                                            </span>
                                            <label class="mt-1 inline-flex cursor-pointer" title="Block ALL new bookings for {{ $typeName }} on {{ $date->format('d M') }} (every plan)">
                                                <input type="checkbox" name="stop_sell[{{ $roomTypeId }}][{{ $date->toDateString() }}]" value="1" @checked($row?->stop_sell) class="peer sr-only">
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black text-emerald-700 ring-1 ring-emerald-200 peer-checked:hidden">Selling</span>
                                                <span class="hidden rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black text-rose-700 ring-1 ring-rose-300 peer-checked:inline">🚫 Blocked</span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                                @foreach ($plans as $plan)
                                    @php $rates = $plan->dailyRates->keyBy(fn ($rate) => $rate->date->toDateString()); @endphp
                                    <tr class="border-t border-slate-100 bg-white">
                                        @php
                                            $mealLabel = match ($plan->meal_plan) {
                                                'cp' => 'Room + breakfast',
                                                'map' => 'Room + breakfast + 1 meal',
                                                'ap' => 'Room + all meals',
                                                default => 'Room only',
                                            };
                                        @endphp
                                        <td colspan="{{ count($dates) + 1 }}" class="px-4 py-2">
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <span class="text-xs font-black text-slate-900">{{ $plan->name }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $mealLabel }}</span>
                                            <span class="text-[10px] font-semibold text-slate-400"
                                                  title="The standard price. Any night without its own price below is sold at this amount. Change it in Room Types & Pricing.">
                                                Standard ₹{{ number_format($plan->default_price_minor / 100) }}/night ⓘ
                                            </span>
                                            <div class="relative ml-auto" data-quickfill>
                                                <button type="button" data-qf-trigger class="inline-flex cursor-pointer select-none items-center gap-1 rounded-md border border-sky-200 bg-sky-50 px-2 py-0.5 text-[11px] font-black text-sky-700 transition hover:bg-sky-100">⚡ Quick fill</button>
                                                <dialog data-qf-dialog aria-label="Quick fill nightly prices" class="qf-dialog">
                                                <div class="qf-dialog__box">
                                                    <header class="flex items-start justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                                                        <div class="min-w-0">
                                                            <p class="text-[10px] font-black uppercase tracking-[.12em] text-sky-700">Quick Fill</p>
                                                            <h3 class="mt-0.5 truncate text-sm font-black text-slate-900">Update {{ $plan->name }}</h3>
                                                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                                <span class="inline-flex max-w-full items-center gap-1 rounded-md bg-sky-100 px-2 py-1 text-[10px] font-black text-sky-800" title="Property: {{ $property->name }}"><span aria-hidden="true">🏨</span><span class="truncate">{{ $property->name }}</span></span>
                                                                <span class="inline-flex items-center rounded-md bg-white px-2 py-1 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200">{{ $start->format('d M') }}–{{ $start->addDays(13)->format('d M Y') }}</span>
                                                            </div>
                                                            <p class="mt-1.5 text-[10px] font-semibold text-slate-500">Apply one rule to selected days on this screen.</p>
                                                        </div>
                                                        <button type="button" data-qf-close aria-label="Close" class="grid h-7 w-7 shrink-0 place-items-center rounded-full border border-slate-200 bg-white text-base font-bold text-slate-500 transition hover:border-slate-300 hover:text-slate-900">×</button>
                                                    </header>
                                                    <div class="p-4">
                                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Price adjustment</p>
                                                    <div class="mt-1.5 grid grid-cols-[minmax(0,1fr)_100px] gap-2">
                                                        <select data-qf-mode class="h-10 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
                                                            <option value="set">Set price to ₹</option>
                                                            <option value="inc_pct">Increase by %</option>
                                                            <option value="dec_pct">Decrease by %</option>
                                                            <option value="inc_amt">Increase by ₹</option>
                                                            <option value="dec_amt">Decrease by ₹</option>
                                                        </select>
                                                        <input type="number" data-qf-value min="0" step="0.01"
                                                               placeholder="{{ number_format($plan->default_price_minor / 100, 0, '.', '') }}"
                                                               class="h-10 w-full rounded-lg border border-slate-300 px-2 text-center text-sm font-black text-slate-900 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
                                                    </div>
                                                    <p class="mt-3 text-[10px] font-black uppercase tracking-wide text-slate-500">Apply to</p>
                                                    <div class="mt-1.5 grid grid-cols-3 gap-1.5" data-qf-days>
                                                        <button type="button" data-days="all" class="h-9 rounded-lg border border-sky-600 bg-sky-600 px-2 text-[11px] font-black text-white">All {{ count($dates) }} days</button>
                                                        <button type="button" data-days="weekday" class="h-9 rounded-lg border border-slate-300 px-2 text-[11px] font-black text-slate-600">Mon–Fri</button>
                                                        <button type="button" data-days="weekend" class="h-9 rounded-lg border border-slate-300 px-2 text-[11px] font-black text-slate-600">Sat–Sun</button>
                                                    </div>
                                                    <button type="button" data-qf-apply data-plan="{{ $plan->id }}"
                                                            class="mt-4 h-10 w-full rounded-lg bg-sky-600 text-sm font-black text-white shadow-sm transition hover:bg-sky-700">Apply to nightly prices</button>
                                                    <p class="mt-2 text-center text-[10px] font-semibold leading-4 text-slate-400">This only prepares the changes. Prices go live after you press <strong class="text-slate-500">Save changes</strong>.</p>
                                                    </div>
                                                </div>
                                                </dialog>
                                            </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-t border-slate-100">
                                        <td class="sticky left-0 z-10 bg-white px-4 py-1.5 text-[10px] font-black uppercase tracking-wide text-slate-400">Nightly price</td>
                                        @foreach ($dates as $date)
                                            @php $rate = $rates->get($date->toDateString()); @endphp
                                            <td class="px-1 py-1.5 text-center">
                                                <input type="number" step="0.01" min="0"
                                                       name="rates[{{ $plan->id }}][{{ $date->toDateString() }}][price]"
                                                       value="{{ $rate ? number_format($rate->price_minor / 100, 2, '.', '') : '' }}"
                                                       placeholder="{{ number_format($plan->default_price_minor / 100, 0, '.', '') }}"
                                                       data-price-input data-plan="{{ $plan->id }}" data-weekend="{{ $date->isWeekend() ? 1 : 0 }}"
                                                       class="h-8 w-[72px] rounded-md border px-1.5 text-center text-xs font-bold {{ $rate?->closed ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-slate-300' }}">
                                                <label class="mt-1 inline-flex cursor-pointer" title="Close only this plan for {{ $date->format('d M') }} — other plans keep selling">
                                                    <input type="checkbox" name="rates[{{ $plan->id }}][{{ $date->toDateString() }}][closed]" value="1" @checked($rate?->closed) class="peer sr-only">
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black text-slate-400 ring-1 ring-slate-200 transition hover:text-slate-600 peer-checked:hidden">Open</span>
                                                    <span class="hidden rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black text-rose-700 ring-1 ring-rose-300 peer-checked:inline">✕ Closed</span>
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </form>

        <style>
            .qf-dialog{width:min(92vw,420px);max-width:none;margin:auto;padding:0;border:0;background:transparent;color:inherit;overflow:visible}
            .qf-dialog::backdrop{background:rgba(15,23,42,.52);backdrop-filter:blur(2px)}
            .qf-dialog__box{width:100%;max-height:min(680px,88vh);overflow:auto;border:1px solid #dbe3ee;border-radius:16px;background:#fff;box-shadow:0 28px 80px rgba(15,23,42,.3)}
            @media(max-width:480px){.qf-dialog{width:calc(100vw - 24px)}.qf-dialog__box{border-radius:13px}}
        </style>

        <script>
            (function () {
                var changed = 0;
                var pendingChip = document.querySelector('[data-qf-pending]');

                function markChanged(input) {
                    if (! input.classList.contains('qf-touched')) {
                        input.classList.add('qf-touched', 'border-amber-400', 'bg-amber-50');
                        changed++;
                    }
                }

                document.querySelectorAll('[data-quickfill]').forEach(function (box) {
                    var dialog = box.querySelector('[data-qf-dialog]');
                    var closeQuickFill = function () {
                        if (dialog?.open) dialog.close();
                        document.body.classList.remove('overflow-hidden');
                    };
                    box.querySelector('[data-qf-trigger]')?.addEventListener('click', function () {
                        document.querySelectorAll('[data-qf-dialog][open]').forEach(function (other) { if (other !== dialog) other.close(); });
                        dialog?.showModal();
                        document.body.classList.add('overflow-hidden');
                        setTimeout(function () { box.querySelector('[data-qf-mode]')?.focus(); }, 0);
                    });
                    box.querySelector('[data-qf-close]')?.addEventListener('click', closeQuickFill);
                    dialog?.addEventListener('cancel', function (event) { event.preventDefault(); closeQuickFill(); });
                    dialog?.addEventListener('click', function (event) {
                        if (event.target === dialog) closeQuickFill();
                    });
                    // Day-scope chips: All / Mon–Fri / Sat–Sun.
                    var scope = 'all';
                    box.querySelectorAll('[data-qf-days] button').forEach(function (chip) {
                        chip.addEventListener('click', function () {
                            scope = chip.dataset.days;
                            box.querySelectorAll('[data-qf-days] button').forEach(function (other) {
                                var active = other === chip;
                                other.classList.toggle('bg-sky-600', active);
                                other.classList.toggle('border-sky-600', active);
                                other.classList.toggle('text-white', active);
                                other.classList.toggle('border-slate-300', ! active);
                                other.classList.toggle('text-slate-600', ! active);
                            });
                        });
                    });

                    box.querySelector('[data-qf-apply]').addEventListener('click', function () {
                        var mode = box.querySelector('[data-qf-mode]').value;
                        var raw = parseFloat(box.querySelector('[data-qf-value]').value);

                        if (isNaN(raw) || raw < 0) {
                            box.querySelector('[data-qf-value]').focus();
                            return;
                        }

                        var planId = this.dataset.plan;
                        document.querySelectorAll('[data-price-input][data-plan="' + planId + '"]').forEach(function (input) {
                            var isWeekend = input.dataset.weekend === '1';
                            if (scope === 'weekday' && isWeekend) return;
                            if (scope === 'weekend' && ! isWeekend) return;

                            // % and ₹ adjustments work from the visible price,
                            // falling back to the plan's standard (placeholder).
                            var base = parseFloat(input.value) || parseFloat(input.placeholder) || 0;
                            var next;
                            switch (mode) {
                                case 'inc_pct': next = Math.round(base * (1 + raw / 100)); break;
                                case 'dec_pct': next = Math.round(base * (1 - raw / 100)); break;
                                case 'inc_amt': next = base + raw; break;
                                case 'dec_amt': next = base - raw; break;
                                default: next = raw;
                            }

                            next = Math.max(0, Math.round(next * 100) / 100);

                            if (input.value !== String(next)) {
                                input.value = next;
                                markChanged(input);
                            }
                        });

                        closeQuickFill();

                        if (changed > 0 && pendingChip) {
                            pendingChip.textContent = changed + ' price' + (changed === 1 ? '' : 's') + ' updated — not saved yet';
                            pendingChip.hidden = false;
                        }
                    });
                });

                // Hand-edited boxes count as pending work too.
                document.querySelectorAll('[data-price-input]').forEach(function (input) {
                    input.addEventListener('input', function () {
                        markChanged(input);
                        if (pendingChip) {
                            pendingChip.textContent = changed + ' price' + (changed === 1 ? '' : 's') + ' updated — not saved yet';
                            pendingChip.hidden = false;
                        }
                    });
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        document.querySelectorAll('[data-qf-dialog][open]').forEach(function (dialog) { dialog.close(); });
                        document.body.classList.remove('overflow-hidden');
                    }
                });
            })();
        </script>
    @endif
@endsection
