@extends('admin.layouts.app')

@section('title', $guest->name)
@section('eyebrow', 'Guest Profile')
@section('page-title', $guest->name)

@section('header-actions')
    <a href="{{ route('admin.guests.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">All Guests</a>
    <a href="{{ route('admin.guests.edit', $guest) }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">Edit</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
        <div class="space-y-6">
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-slate-500">Guest account</p>
                        <h2 class="mt-1 text-2xl font-black">{{ $guest->name }}</h2>
                        <p class="mt-2 text-sm font-semibold text-slate-500">{{ $guest->email }} {{ $guest->phone ? ' / '.$guest->phone : '' }}</p>
                        @if($guest->corporate)<p class="mt-1 text-xs font-black text-blue-700">{{ $guest->corporate->legal_name }} · GSTIN {{ $guest->corporate->gstin }}</p>@endif
                    </div>
                    <span class="w-fit rounded-full bg-slate-950 px-3 py-1 text-xs font-black uppercase tracking-wide text-white">{{ $guest->is_active ? 'Active' : 'Inactive' }}</span>
                </div>

                <div class="mt-6 grid gap-3 md:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Bookings</p><p class="mt-2 text-2xl font-black">{{ $stats['bookings'] }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Nights</p><p class="mt-2 text-2xl font-black">{{ $stats['nights'] }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Spent</p><p class="mt-2 text-2xl font-black">INR {{ number_format($stats['spent'] / 100, 2) }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Last stay</p><p class="mt-2 text-xl font-black">{{ $stats['lastBooking'] ? $stats['lastBooking']->format('M j, Y') : '-' }}</p></div>
                </div>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-black">Booking History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Booking</th>
                                <th class="px-5 py-3">Property</th>
                                <th class="px-5 py-3">Room</th>
                                <th class="px-5 py-3">Dates</th>
                                <th class="px-5 py-3">Amount</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($guest->bookings as $booking)
                                <tr>
                                    <td class="px-5 py-4 font-black"><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->booking_number }}</a></td>
                                    <td class="px-5 py-4 text-slate-600">{{ $booking->property->name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $booking->room->room_number }} / {{ $booking->roomType->name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $booking->check_in_date->format('M j') }} - {{ $booking->check_out_date->format('M j, Y') }}</td>
                                    <td class="px-5 py-4 font-bold">{{ $booking->formattedTotal() }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ str_replace('_', ' ', ucfirst($booking->status)) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center font-semibold text-slate-500">No booking history yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Identity</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">DOB</dt><dd class="font-black">{{ optional($guest->date_of_birth)->format('M j, Y') ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Gender</dt><dd class="font-black">{{ $guest->gender ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Nationality</dt><dd class="font-black">{{ $guest->nationality ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Document</dt><dd class="font-black">{{ $guest->id_document_type ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Number</dt><dd class="font-black">{{ $guest->id_document_number ?: '-' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Address</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $guest->formattedAddress() ?: 'No address added.' }}</p>
            </section>

            @if($guest->corporate)
                <section class="rounded-lg border border-blue-200 bg-blue-50/60 p-5 shadow-sm"><div class="flex items-center justify-between"><h2 class="text-lg font-black text-blue-950">Corporate account</h2><span class="rounded bg-blue-100 px-2 py-1 text-[10px] font-black text-blue-700">B2B</span></div><dl class="mt-4 space-y-2 text-sm"><div class="flex justify-between gap-4"><dt class="text-slate-500">Legal name</dt><dd class="font-black text-right">{{ $guest->corporate->legal_name }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">GSTIN</dt><dd class="font-mono font-black">{{ $guest->corporate->gstin }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">PAN</dt><dd class="font-mono font-black">{{ $guest->corporate->pan ?: '-' }}</dd></div><div class="border-t border-blue-100 pt-2 text-slate-600">{{ $guest->corporate->formattedAddress() }}</div></dl></section>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Private Notes</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $guest->guest_notes ?: 'No notes.' }}</p>
            </section>

            @if ($guest->is_active)
                <form method="POST" action="{{ route('admin.guests.destroy', $guest) }}" class="rounded-lg border border-rose-200 bg-rose-50 p-5">
                    @csrf
                    @method('DELETE')
                    <h2 class="text-lg font-black text-rose-950">Deactivate Guest</h2>
                    <p class="mt-2 text-sm font-semibold text-rose-700">This disables login without deleting booking history.</p>
                    <button class="mt-4 h-10 rounded-lg bg-rose-700 px-4 text-sm font-bold text-white" onclick="return confirm('Deactivate this guest?')">Deactivate</button>
                </form>
            @endif
        </aside>
    </section>
@endsection
