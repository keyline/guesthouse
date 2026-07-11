@extends('public.booking.layout')

@section('title', 'Book Your Stay')

@section('content')
    <h1 class="text-3xl font-black tracking-tight">Book your stay</h1>
    <p class="mt-1 text-sm font-semibold text-slate-500">Live availability and instant reservation. Pay at the property.</p>

    <form method="GET" action="{{ route('book.search') }}" class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1.4fr_1fr_1fr_auto]">
        <div>
            <label for="property_id" class="text-xs font-black uppercase tracking-wide text-slate-500">Property</label>
            <select id="property_id" name="property_id" required class="mt-1 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold">
                <option value="">Choose a guest house</option>
                @foreach ($properties as $option)
                    <option value="{{ $option->id }}" @selected($property && $property->id === $option->id)>{{ $option->name }} — {{ $option->city }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="check_in" class="text-xs font-black uppercase tracking-wide text-slate-500">Check-in</label>
            <input id="check_in" type="date" name="check_in" min="{{ now()->toDateString() }}" value="{{ $checkIn->toDateString() }}" required class="mt-1 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold">
        </div>
        <div>
            <label for="check_out" class="text-xs font-black uppercase tracking-wide text-slate-500">Check-out</label>
            <input id="check_out" type="date" name="check_out" min="{{ now()->addDay()->toDateString() }}" value="{{ $checkOut->toDateString() }}" required class="mt-1 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold">
        </div>
        <div class="flex items-end">
            <button class="h-11 w-full rounded-lg bg-sky-600 px-6 text-sm font-black text-white transition hover:bg-sky-700 sm:w-auto">Search</button>
        </div>
    </form>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($searched)
        <div class="mt-8">
            <h2 class="text-lg font-black">{{ $property->name }} — {{ $checkIn->format('d M') }} to {{ $checkOut->format('d M Y') }} · {{ $nights }} {{ Str::plural('night', $nights) }}</h2>

            @forelse ($results as $result)
                <article class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="text-xl font-black">{{ $result['roomType']->name }}</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500">
                                Up to {{ $result['roomType']->max_adults }} adults{{ $result['roomType']->max_children ? ' + '.$result['roomType']->max_children.' children' : '' }}
                                @if ($result['roomType']->amenities->isNotEmpty())
                                    · {{ $result['roomType']->amenities->take(4)->pluck('name')->implode(' · ') }}
                                @endif
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $result['sellable'] <= 2 ? 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' : 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' }}">
                            {{ $result['sellable'] <= 2 ? 'Only '.$result['sellable'].' left!' : $result['sellable'].' rooms available' }}
                        </span>
                    </div>

                    @foreach ($result['plans'] as $row)
                        <details class="group border-b border-slate-100 last:border-0">
                            <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50">
                                <div>
                                    <p class="text-sm font-black text-slate-900">{{ $row['plan']->name }}</p>
                                    <p class="text-xs font-semibold text-slate-500">
                                        {{ \App\Models\RatePlan::mealPlans()[$row['plan']->meal_plan] ?? strtoupper($row['plan']->meal_plan) }}
                                        · {{ $row['plan']->is_refundable ? 'Free cancellation' : 'Non-refundable' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-black text-slate-950">₹{{ number_format($row['totalMinor'] / 100) }}</p>
                                    <p class="text-[11px] font-bold text-slate-500">total for {{ $nights }} {{ Str::plural('night', $nights) }}</p>
                                    <span class="mt-1 inline-block rounded-lg bg-sky-600 px-4 py-1.5 text-xs font-black text-white group-open:hidden">Book</span>
                                </div>
                            </summary>
                            <form method="POST" action="{{ route('book.store') }}" class="grid gap-3 bg-slate-50 px-5 py-4 sm:grid-cols-2">
                                @csrf
                                <input type="hidden" name="rate_plan_id" value="{{ $row['plan']->id }}">
                                <input type="hidden" name="check_in" value="{{ $checkIn->toDateString() }}">
                                <input type="hidden" name="check_out" value="{{ $checkOut->toDateString() }}">
                                <div>
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Guest name *</label>
                                    <input name="guest_name" value="{{ old('guest_name', auth()->user()?->name) }}" required class="mt-1 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold">
                                </div>
                                <div>
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Phone *</label>
                                    <input name="guest_phone" value="{{ old('guest_phone', auth()->user()?->phone) }}" required class="mt-1 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold">
                                </div>
                                <div>
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Email</label>
                                    <input name="guest_email" type="email" value="{{ old('guest_email', auth()->user()?->email) }}" class="mt-1 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Adults</label>
                                        <input name="adults" type="number" min="1" max="10" value="{{ old('adults', 2) }}" required class="mt-1 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold">
                                    </div>
                                    <div>
                                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Children</label>
                                        <input name="children" type="number" min="0" max="10" value="{{ old('children', 0) }}" required class="mt-1 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold">
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Special requests</label>
                                    <textarea name="special_requests" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">{{ old('special_requests') }}</textarea>
                                </div>
                                <div class="flex items-center justify-between sm:col-span-2">
                                    <p class="text-xs font-bold text-slate-500">No prepayment — pay at the property. The property confirms your booking.</p>
                                    <button class="h-11 rounded-lg bg-sky-600 px-6 text-sm font-black text-white transition hover:bg-sky-700">Reserve for ₹{{ number_format($row['totalMinor'] / 100) }}</button>
                                </div>
                            </form>
                        </details>
                    @endforeach
                </article>
            @empty
                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <p class="text-lg font-black text-slate-800">No rooms available online for these dates.</p>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Try different dates, or call the property directly — walk-in inventory may still be open.</p>
                </div>
            @endforelse
        </div>
    @endif
@endsection
