@extends('admin.layouts.app')

@section('title', $booking->booking_number)
@section('eyebrow', 'Booking Profile')
@section('page-title', $booking->booking_number)

@section('header-actions')
    <a href="{{ route('admin.bookings.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">All Bookings</a>
    <a href="{{ route('admin.bookings.edit', $booking) }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">Edit</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $booking->property->name }}</p>
                    <h2 class="mt-1 text-2xl font-black">{{ $booking->guest_name }}</h2>
                    <p class="mt-2 text-sm font-semibold text-slate-500">{{ $booking->roomType->name }} / {{ $booking->room ? 'Room '.$booking->room->room_number : 'Room not assigned yet' }}</p>
                </div>
                <span class="w-fit rounded-full bg-slate-950 px-3 py-1 text-xs font-black uppercase tracking-wide text-white">{{ str_replace('_', ' ', $booking->status) }}</span>
            </div>

            <div class="mt-6 grid gap-3 md:grid-cols-4">
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Check-in</p><p class="mt-2 text-xl font-black">{{ $booking->check_in_date->format('M j, Y') }}</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Check-out</p><p class="mt-2 text-xl font-black">{{ $booking->check_out_date->format('M j, Y') }}</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Nights</p><p class="mt-2 text-xl font-black">{{ $booking->nights }}</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Total</p><p class="mt-2 text-xl font-black">{{ $booking->formattedTotal() }}</p></div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="font-black">Guest Contact</h3>
                    <p class="mt-2 text-sm font-semibold text-slate-600">{{ $booking->guest_phone ?: 'No phone' }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-600">{{ $booking->guest_email ?: 'No email' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="font-black">Guests</h3>
                    <p class="mt-2 text-sm font-semibold text-slate-600">{{ $booking->adults }} adults, {{ $booking->children }} children</p>
                    <p class="mt-1 text-sm font-semibold text-slate-600">Source: {{ ucfirst(str_replace('_', ' ', $booking->source)) }}</p>
                </div>
            </div>
        </article>

        <aside class="space-y-6">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Requests</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $booking->special_requests ?: 'No special requests.' }}</p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Internal Notes</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $booking->internal_notes ?: 'No internal notes.' }}</p>
            </section>

            @if ($booking->status !== 'cancelled')
                <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}" class="rounded-lg border border-rose-200 bg-rose-50 p-5">
                    @csrf
                    @method('DELETE')
                    <h2 class="text-lg font-black text-rose-950">Cancel Booking</h2>
                    <p class="mt-2 text-sm font-semibold text-rose-700">Cancellation releases the room for future availability checks.</p>
                    <button class="mt-4 h-10 rounded-lg bg-rose-700 px-4 text-sm font-bold text-white" onclick="return confirm('Cancel this booking?')">Cancel Booking</button>
                </form>
            @endif
        </aside>
    </section>
@endsection
