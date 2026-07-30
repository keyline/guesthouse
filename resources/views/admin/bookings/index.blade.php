@extends('admin.layouts.app')

@section('title', 'Bookings')
@section('eyebrow', 'Booking Desk')
@section('page-title', 'Bookings')

@section('header-actions')
    @if (($pendingRefundCount ?? 0) > 0)
        <a href="{{ route('admin.bookings.pending-refunds') }}" class="rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-[11px] font-bold text-amber-900 hover:bg-amber-100 transition">Pending refunds <span class="ml-1 rounded-full bg-amber-600 px-1.5 py-0.5 text-[9px] font-black text-white">{{ $pendingRefundCount }}</span></a>
    @endif
    <a href="{{ route('admin.availability.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition">Availability</a>
    <a href="{{ route('admin.bookings.create') }}" class="rounded-md bg-sky-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-sky-700 transition">+ New Booking</a>
@endsection

@section('content')
    <style>
        .booking-register { color: #334155; font-size: 10px; }
        .booking-register-toolbar,
        .booking-register-grid { border: 1px solid #dbe3ee; border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgb(15 23 42 / .05); }
        .booking-register-toolbar { display: grid; grid-template-columns: auto 165px 145px 145px auto; align-items: end; gap: 7px; padding: 8px 10px; }
        .booking-scope-chip { display: flex; height: 30px; align-items: center; gap: 5px; border: 1px solid #bae6fd; border-radius: 5px; background: #f0f9ff; padding: 0 9px; color: #075985; font-size: 9px; font-weight: 800; white-space: nowrap; }
        .booking-scope-chip svg { width: 12px; height: 12px; }
        .booking-field-label { display: block; color: #64748b; font-size: 8px; font-weight: 900; letter-spacing: .08em; line-height: 1; text-transform: uppercase; }
        .booking-field-control { display: block; width: 100%; height: 30px; margin-top: 4px; border: 1px solid #cbd5e1; border-radius: 5px; background: #fff; padding: 0 8px; color: #1e293b; font-size: 10px; font-weight: 650; outline: none; }
        .booking-field-control:focus { border-color: #38bdf8; box-shadow: 0 0 0 2px rgb(56 189 248 / .15); }
        .booking-filter-actions { display: flex; gap: 5px; }
        .booking-filter-button { display: inline-flex; height: 30px; align-items: center; justify-content: center; border-radius: 5px; padding: 0 11px; font-size: 10px; font-weight: 900; }
        .booking-filter-button.is-primary { border: 1px solid #0284c7; background: #0284c7; color: #fff; }
        .booking-filter-button.is-secondary { border: 1px solid #cbd5e1; background: #fff; color: #475569; }
        .booking-register-grid { margin-top: 8px; overflow: hidden; }
        .booking-grid-heading { display: flex; min-height: 29px; align-items: center; justify-content: space-between; gap: 8px; padding: 5px 9px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
        .booking-grid-heading h2 { color: #0f172a; font-size: 10px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
        .booking-grid-heading p { color: #64748b; font-size: 9px; font-weight: 700; }
        .booking-grid-scroll { overflow: auto; }
        .booking-grid-table { width: 100%; min-width: 1110px; border-collapse: collapse; text-align: left; font-size: 10px; line-height: 1.17; }
        .booking-grid-table thead { position: sticky; top: 0; z-index: 1; background: #f8fafc; color: #64748b; }
        .booking-grid-table th { height: 27px; padding: 4px 8px; border-bottom: 1px solid #dbe3ee; font-size: 8px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; }
        .booking-grid-table td { height: 36px; padding: 4px 8px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        .booking-grid-table tbody tr:last-child td { border-bottom: 0; }
        .booking-grid-table tbody tr:hover { background: #f8fafc; }
        .booking-cell-primary { display: block; color: #1e293b; font-size: 10px; font-weight: 850; white-space: nowrap; }
        .booking-cell-secondary { display: block; overflow: hidden; max-width: 215px; margin-top: 1px; color: #64748b; font-size: 8px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .booking-money { font-variant-numeric: tabular-nums; font-weight: 900; }
        .booking-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 6px; font-size: 7px; font-weight: 900; letter-spacing: .05em; line-height: 1.1; text-transform: uppercase; white-space: nowrap; }
        .booking-action { color: #0369a1; font-size: 9px; font-weight: 850; }
        .booking-action + .booking-action { margin-left: 7px; color: #475569; }
        .booking-pagination { margin-top: 8px; font-size: 10px; }
        .booking-flash { margin-bottom: 8px; border: 1px solid #a7f3d0; border-radius: 6px; background: #ecfdf5; padding: 6px 9px; color: #047857; font-size: 10px; font-weight: 800; }
        @media (max-width: 1000px) {
            .booking-register-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .booking-scope-chip { grid-column: 1 / -1; }
        }
        @media (max-width: 640px) {
            .booking-register-toolbar { grid-template-columns: 1fr; }
            .booking-scope-chip { grid-column: auto; }
        }
    </style>

    <div class="booking-register">
    @if (session('status'))
        <div class="booking-flash">{{ session('status') }}</div>
    @endif

    <section>
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="booking-register-toolbar">
            <div class="booking-scope-chip" title="Change the property from the selector in the page header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1M9 13h1m4 0h1M9 17h6"/></svg>
                Property scope follows header selector
            </div>
            <div>
                <label for="status" class="booking-field-label">Stay status</label>
                <select id="status" name="status" class="booking-field-control">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="booking-field-label">Stay from</label>
                <input id="from" name="from" type="date" value="{{ request('from') }}" class="booking-field-control">
            </div>
            <div>
                <label for="to" class="booking-field-label">Stay to</label>
                <input id="to" name="to" type="date" value="{{ request('to') }}" class="booking-field-control">
            </div>
            <div class="booking-filter-actions">
                <button class="booking-filter-button is-primary">Apply</button>
                <a href="{{ route('admin.bookings.index') }}" class="booking-filter-button is-secondary">Clear</a>
            </div>
        </form>
    </section>

    <section class="booking-register-grid">
        <div class="booking-grid-heading">
            <h2>Booking register</h2>
            <p>{{ $bookings->total() }} record(s) · ordered by arrival date</p>
        </div>
        <div class="booking-grid-scroll">
            <table class="booking-grid-table">
                <thead>
                    <tr>
                        <th>Booking / Created</th><th>Guest</th><th>Property / Room</th><th>Stay</th><th>Payable</th><th>Payment</th><th>Stay status</th><th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>
                                <span class="booking-cell-primary">{{ $booking->booking_number }}</span>
                                <span class="booking-cell-secondary" title="Reservation created at {{ $booking->created_at->format('d M Y, h:i A') }}">{{ $booking->created_at->format('d M Y') }} · {{ $booking->created_at->format('h:i A') }}</span>
                            </td>
                            <td>
                                <span class="booking-cell-primary">{{ $booking->guest_name }}</span>
                                <span class="booking-cell-secondary">{{ $booking->guest_phone ?: $booking->guest_email }}</span>
                            </td>
                            <td>
                                <span class="booking-cell-primary">{{ $booking->property->name }}</span>
                                <span class="booking-cell-secondary">{{ $booking->room?->room_number ?? 'Unassigned' }} · {{ $booking->roomType->name }}
                                @if ($booking->ratePlan && $booking->ratePlan->meal_plan !== 'ep') <span class="ml-1 rounded bg-amber-50 px-1 text-[9px] font-black text-amber-800">{{ strtoupper($booking->ratePlan->meal_plan) }}</span>@endif
                                </span>
                            </td>
                            <td>
                                <span class="booking-cell-primary">{{ $booking->check_in_date->format('d M') }} → {{ $booking->check_out_date->format('d M Y') }}</span>
                                <span class="booking-cell-secondary">{{ $booking->nights }} {{ Str::plural('night', $booking->nights) }}</span>
                            </td>
                            <td>
                                <span class="booking-cell-primary booking-money">{{ $booking->formattedGrossTotal() }}</span>
                                @if ($booking->tax_amount_minor > 0)<span class="booking-cell-secondary">GST included</span>@endif
                            </td>
                            <td>
                                @php
                                    $paymentTone = match ($booking->payment_status) {
                                        \App\Models\Booking::PAYMENT_PAID => 'bg-emerald-50 text-emerald-700',
                                        \App\Models\Booking::PAYMENT_REFUNDED => 'bg-violet-50 text-violet-700',
                                        default => 'bg-amber-50 text-amber-800',
                                    };
                                @endphp
                                <span class="booking-cell-primary">{{ $booking->paymentOptionLabel() }}</span>
                                <span class="booking-pill {{ $paymentTone }}">{{ $booking->payment_status }}</span>
                                @if ($booking->outstandingAmountMinor() > 0 && $booking->payment_status !== \App\Models\Booking::PAYMENT_REFUNDED)
                                    <span class="ml-1 text-[8px] font-bold text-rose-600">Due {{ $booking->currency }} {{ number_format($booking->outstandingAmountMinor() / 100, 2) }}</span>
                                @endif
                            </td>
                            <td><span class="booking-pill bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="booking-action">Open</a>
                                <a href="{{ route('admin.bookings.edit', $booking) }}" class="booking-action">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center">
                                <h2 class="text-sm font-black">No bookings yet</h2>
                                <p class="mt-1 text-[10px] font-semibold text-slate-500">Create a direct booking after properties, room types, and rooms are ready.</p>
                                <a href="{{ route('admin.bookings.create') }}" class="mt-3 inline-flex h-8 items-center rounded-md bg-sky-600 px-3 text-[10px] font-bold text-white">New Booking</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="booking-pagination">{{ $bookings->links() }}</div>
    </div>
@endsection
