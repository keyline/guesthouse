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
                    <button type="submit" class="h-10 rounded-lg bg-sky-600 px-5 text-sm font-black text-white transition hover:bg-sky-700">Save changes</button>
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
                                    <tr class="border-t border-slate-100">
                                        @php
                                            $mealLabel = match ($plan->meal_plan) {
                                                'cp' => 'Room + breakfast',
                                                'map' => 'Room + breakfast + 1 meal',
                                                'ap' => 'Room + all meals',
                                                default => 'Room only',
                                            };
                                        @endphp
                                        <td class="sticky left-0 bg-white px-4 py-2">
                                            <span class="block text-sm font-black text-slate-900">{{ $plan->name }}</span>
                                            <span class="block text-[11px] font-semibold text-slate-500">{{ $mealLabel }}</span>
                                            <span class="block text-[11px] font-semibold text-slate-400"
                                                  title="The standard price. Any night without its own price below is sold at this amount. Change it in Room Types & Pricing.">
                                                Standard ₹{{ number_format($plan->default_price_minor / 100) }}/night ⓘ
                                            </span>
                                        </td>
                                        @foreach ($dates as $date)
                                            @php $rate = $rates->get($date->toDateString()); @endphp
                                            <td class="px-1 py-1.5 text-center">
                                                <input type="number" step="0.01" min="0"
                                                       name="rates[{{ $plan->id }}][{{ $date->toDateString() }}][price]"
                                                       value="{{ $rate ? number_format($rate->price_minor / 100, 2, '.', '') : '' }}"
                                                       placeholder="{{ number_format($plan->default_price_minor / 100, 0, '.', '') }}"
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
    @endif
@endsection
