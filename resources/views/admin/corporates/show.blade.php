@extends('admin.layouts.app')

@section('title', $corporate->displayName())
@section('eyebrow', 'People')
@section('page-title', $corporate->displayName())

@section('header-actions')
    <a href="{{ route('admin.corporates.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">All Companies</a>
    <a href="{{ route('admin.corporates.edit', $corporate) }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">Edit</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold text-slate-500">Company</p>
            <p class="mt-1 font-black text-slate-900">{{ $corporate->legal_name }}</p>
            <p class="text-xs font-semibold text-slate-500">GSTIN {{ $corporate->gstin }}{{ $corporate->pan ? ' · PAN '.$corporate->pan : '' }}</p>
            <p class="mt-2 text-xs text-slate-600">{{ $corporate->formattedAddress() }}</p>
            @if ($corporate->contact_name || $corporate->email || $corporate->phone)
                <p class="mt-2 text-xs font-semibold text-slate-600">{{ collect([$corporate->contact_name, $corporate->email, $corporate->phone])->filter()->join(' · ') }}</p>
            @endif
            <span class="mt-3 inline-flex rounded px-2 py-1 text-xs font-black {{ $corporate->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                {{ $corporate->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold text-slate-500">Rate agreement</p>
            <p class="mt-1 text-sm font-bold text-slate-800">
                Booking code:
                @if ($corporate->booking_code)
                    <span class="rounded bg-amber-100 px-2 py-0.5 font-mono text-xs font-black text-amber-800">{{ $corporate->booking_code }}</span>
                @else
                    <span class="text-slate-400">none (front desk only)</span>
                @endif
            </p>
            @if ($corporate->discount_type)
                <p class="mt-2 text-sm font-semibold text-slate-700">Blanket discount:
                    {{ $corporate->discount_type === \App\Models\Discount::TYPE_PERCENT
                        ? rtrim(rtrim(number_format($corporate->discount_value / 100, 2), '0'), '.').'% off'
                        : '₹'.number_format($corporate->discount_value / 100).' off per stay' }}
                </p>
            @endif
            @if ($corporate->roomRates->isNotEmpty())
                <table class="mt-3 w-full text-xs">
                    <thead><tr class="text-left font-bold uppercase tracking-wide text-slate-500"><th class="py-1">Room type</th><th class="py-1 text-right">Company price / night</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($corporate->roomRates as $rate)
                            <tr>
                                <td class="py-1.5 font-semibold text-slate-700">{{ $rate->roomType?->name ?? 'Room type #'.$rate->room_type_id }}</td>
                                <td class="py-1.5 text-right font-black text-slate-900">₹{{ number_format($rate->price_minor / 100) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif (! $corporate->discount_type)
                <p class="mt-2 text-xs text-slate-500">No negotiated prices or discount yet — employees pay normal rates.</p>
            @endif
        </div>

        <div class="rounded-lg border {{ $unbilledCount ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' }} p-4">
            <p class="text-xs font-bold {{ $unbilledCount ? 'text-amber-700' : 'text-slate-500' }}">To be billed to company</p>
            <p class="mt-1 text-2xl font-black {{ $unbilledCount ? 'text-amber-800' : 'text-slate-400' }}">₹{{ number_format($unbilledMinor / 100, 2) }}</p>
            <p class="text-xs font-semibold {{ $unbilledCount ? 'text-amber-700' : 'text-slate-500' }}">{{ $unbilledCount }} unpaid bill-to-company {{ Str::plural('stay', $unbilledCount) }}</p>
            <p class="mt-2 text-[11px] text-slate-500">Mark a booking paid from its edit screen once the company settles the invoice.</p>
        </div>
    </div>

    <div class="mt-6 overflow-hidden border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h3 class="text-sm font-black text-slate-900">Bookings</h3>
        </div>
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                    <th class="px-4 py-2.5">Booking</th>
                    <th class="px-4 py-2.5">Guest</th>
                    <th class="px-4 py-2.5">Stay</th>
                    <th class="px-4 py-2.5">Billing</th>
                    <th class="px-4 py-2.5 text-right">Amount</th>
                    <th class="px-4 py-2.5 text-center">Payment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($bookings as $booking)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="font-black text-slate-900 hover:underline">{{ $booking->booking_number }}</a>
                            <p class="text-xs font-semibold text-slate-500">{{ ucfirst($booking->status) }} · {{ $booking->property?->name }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-700">{{ $booking->guest_name }}</td>
                        <td class="px-4 py-3 text-xs font-semibold text-slate-600">{{ $booking->check_in_date->format('d M') }} – {{ $booking->check_out_date->format('d M Y') }} · {{ $booking->roomType?->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded px-2 py-1 text-xs font-black {{ $booking->billing === \App\Models\Booking::BILLING_CORPORATE ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $booking->billing === \App\Models\Booking::BILLING_CORPORATE ? 'Bill to company' : 'Guest pays' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-slate-800">{{ $booking->formattedGrossTotal() }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-1 text-xs font-black {{ $booking->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($booking->payment_status === 'refunded' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800') }}">
                                {{ ucfirst($booking->payment_status ?: 'unpaid') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm font-semibold text-slate-500">No bookings from this company yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $bookings->links() }}
    </div>
@endsection
