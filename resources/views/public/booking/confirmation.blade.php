@extends('public.booking.layout')

@section('title', 'Booking '.$booking->booking_number)

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <p class="text-4xl">✓</p>
            <h1 class="mt-2 text-2xl font-black text-emerald-950">Reservation received!</h1>
            <p class="mt-1 text-sm font-bold text-emerald-800">Your booking reference is <span class="rounded bg-white px-2 py-0.5 font-black">{{ $booking->booking_number }}</span></p>
            <p class="mt-2 text-xs font-semibold text-emerald-700">Status: pending confirmation by the property. You'll be contacted on {{ $booking->guest_phone }}.</p>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black">{{ $booking->property->name }}</h2>
            <p class="text-sm font-semibold text-slate-500">{{ $booking->property->address }}, {{ $booking->property->city }}</p>

            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Check-in</dt>
                    <dd class="mt-1 text-lg font-black">{{ $booking->check_in_date->format('D, d M Y') }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Check-out</dt>
                    <dd class="mt-1 text-lg font-black">{{ $booking->check_out_date->format('D, d M Y') }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Room</dt>
                    <dd class="mt-1 text-sm font-black">{{ $booking->roomType->name }}</dd>
                    <dd class="text-xs font-semibold text-slate-500">{{ $booking->ratePlan?->name }} · {{ $booking->adults }} adults{{ $booking->children ? ', '.$booking->children.' children' : '' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Total ({{ $booking->nights }} {{ Str::plural('night', $booking->nights) }})</dt>
                    <dd class="mt-1 text-lg font-black">{{ $booking->formattedTotal() }}</dd>
                    <dd class="text-xs font-semibold text-slate-500">Pay at the property</dd>
                </div>
            </dl>
        </div>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('book.search') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Book another stay</a>
            @auth
                <a href="{{ route('customer.dashboard') }}" class="inline-flex h-11 items-center rounded-lg bg-sky-600 px-5 text-sm font-black text-white">View my bookings</a>
            @endauth
        </div>
    </div>
@endsection
