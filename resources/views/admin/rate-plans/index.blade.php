@extends('admin.layouts.app')

@section('title', 'Room Types & Pricing')
@section('eyebrow', 'Revenue')
@section('page-title', 'Room Types & Pricing')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ $errors->first() }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.rate-plans.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="property_id" class="text-xs font-black uppercase tracking-wide text-slate-500">Property</label>
                <select id="property_id" name="property_id" onchange="this.form.submit()" class="mt-1 h-10 w-64 rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold">
                    @foreach ($properties as $option)
                        <option value="{{ $option->id }}" @selected($property && $property->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <p class="pb-2 text-xs font-semibold text-slate-500">Prices here are the everyday (rack) rate per night. Seasonal overrides live in the <a href="{{ route('admin.rate-calendar.index', ['property_id' => $property?->id]) }}" class="font-black text-sky-700">Rate Calendar</a>.</p>
        </form>
    </section>

    @if (! $property)
        <section class="mt-4 rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-bold text-slate-700">No property available.</p>
        </section>
    @else
        @forelse ($roomTypes as $roomType)
            @php
                $typePlans = $plans->get($roomType->id, collect());
                $counts = $roomCounts->get($roomType->id);
                $epPlan = $typePlans->first(fn ($plan) => $plan->meal_plan === \App\Models\RatePlan::MEAL_PLAN_EP && $plan->status === \App\Models\RatePlan::STATUS_ACTIVE);
                $existingCodes = $typePlans->pluck('code')->all();
            @endphp
            <section class="mt-3 rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5">
                    <h3 class="text-sm font-black text-slate-950">
                        {{ $roomType->name }}
                        <span class="ml-2 text-[11px] font-bold text-slate-500">{{ (int) ($counts->total ?? 0) }} rooms · {{ (int) ($counts->online ?? 0) }} online</span>
                    </h3>
                </div>

                <div class="divide-y divide-slate-50">
                    @foreach ($typePlans as $plan)
                        <div class="flex flex-wrap items-center gap-3 px-4 py-2.5 {{ $plan->status === \App\Models\RatePlan::STATUS_ACTIVE ? '' : 'opacity-50' }}">
                            <div class="w-64 min-w-0">
                                <p class="truncate text-sm font-black text-slate-900">{{ $plan->name }}</p>
                                <p class="text-[11px] font-semibold text-slate-500">{{ $mealPlans[$plan->meal_plan] ?? strtoupper($plan->meal_plan) }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.rate-plans.update', $plan) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <span class="text-sm font-black text-slate-500">₹</span>
                                <input type="number" name="price" step="0.01" min="0"
                                       value="{{ number_format($plan->default_price_minor / 100, 2, '.', '') }}"
                                       class="h-9 w-28 rounded-lg border border-slate-300 px-2 text-right text-sm font-black">
                                <button class="h-9 rounded-lg border border-sky-600 bg-sky-600 px-3 text-xs font-black text-white transition hover:bg-sky-700">Save</button>
                            </form>
                            <span class="text-[11px] font-semibold text-slate-400">per night</span>
                            <form method="POST" action="{{ route('admin.rate-plans.toggle', $plan) }}" class="ml-auto">
                                @csrf
                                <button class="rounded-lg border px-3 py-1.5 text-[11px] font-bold transition {{ $plan->status === \App\Models\RatePlan::STATUS_ACTIVE ? 'border-slate-300 text-slate-600 hover:bg-slate-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                                    {{ $plan->status === \App\Models\RatePlan::STATUS_ACTIVE ? 'Deactivate' : 'Reactivate' }}
                                </button>
                            </form>
                        </div>
                    @endforeach

                    <div class="bg-slate-50/60 px-4 py-2.5">
                        <form method="POST" action="{{ route('admin.rate-plans.store') }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="hidden" name="property_id" value="{{ $property->id }}">
                            <input type="hidden" name="room_type_id" value="{{ $roomType->id }}">
                            <span class="text-xs font-black uppercase tracking-wide text-slate-500">+ Add plan</span>
                            <select name="meal_plan" class="h-9 rounded-lg border border-slate-300 bg-white px-2 text-xs font-bold">
                                @foreach ($mealPlans as $code => $label)
                                    @continue(in_array(strtoupper($code), $existingCodes, true))
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="text-xs font-bold text-slate-500">{{ $epPlan ? 'supplement' : 'price' }} ₹</span>
                            <input type="number" name="amount" step="0.01" min="0" required placeholder="{{ $epPlan ? '300' : '2000' }}"
                                   class="h-9 w-24 rounded-lg border border-slate-300 px-2 text-right text-xs font-black">
                            <button class="h-9 rounded-lg border border-slate-400 bg-white px-3 text-xs font-black text-slate-700 transition hover:bg-slate-100">Add</button>
                            @if ($epPlan)
                                <span class="text-[11px] font-semibold text-slate-400">= EP price + supplement, seeded onto future nights</span>
                            @endif
                        </form>
                    </div>
                </div>
            </section>
        @empty
            <section class="mt-4 rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-bold text-slate-700">{{ $property->name }} has no rooms yet — add rooms first, then price their room types here.</p>
            </section>
        @endforelse
    @endif
@endsection
