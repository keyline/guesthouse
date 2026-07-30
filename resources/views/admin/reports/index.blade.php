@extends('admin.layouts.app')

@section('title', 'Reports')
@section('eyebrow', 'Business Intelligence')
@section('page-title', 'Reports')

@section('content')
    <style>
        .erp-report-page { font-size: 11px; color: #334155; }
        .erp-report-toolbar,
        .erp-report-grid { border: 1px solid #dbe3ee; background: #fff; box-shadow: 0 1px 2px rgb(15 23 42 / .05); }
        .erp-report-toolbar { border-radius: 9px; padding: 8px 10px; }
        .erp-report-toolbar-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 7px; }
        .erp-report-tabs { display: inline-flex; align-items: center; gap: 2px; padding: 2px; border-radius: 6px; background: #eef2f7; }
        .erp-report-tab { display: inline-flex; height: 26px; align-items: center; border-radius: 5px; padding: 0 12px; color: #64748b; font-size: 10px; font-weight: 800; white-space: nowrap; }
        .erp-report-tab.is-active { background: #fff; color: #0369a1; box-shadow: 0 1px 2px rgb(15 23 42 / .12); }
        .erp-report-scope { display: flex; min-width: 0; align-items: center; gap: 5px; border-radius: 5px; border: 1px solid #bae6fd; background: #f0f9ff; padding: 4px 8px; color: #075985; font-size: 10px; line-height: 1.2; }
        .erp-report-scope strong { overflow: hidden; max-width: 240px; text-overflow: ellipsis; white-space: nowrap; }
        .erp-report-scope span { color: #0284c7; }
        .erp-report-filter-grid { display: grid; grid-template-columns: 138px 138px 160px minmax(210px, 1fr) auto; align-items: end; gap: 7px; }
        .erp-report-label { display: block; color: #64748b; font-size: 8px; font-weight: 900; letter-spacing: .08em; line-height: 1; text-transform: uppercase; }
        .erp-report-control { display: block; width: 100%; height: 30px; margin-top: 4px; border: 1px solid #cbd5e1; border-radius: 5px; background: #fff; padding: 0 8px; color: #1e293b; font-size: 10px; font-weight: 650; outline: none; }
        .erp-report-control:focus { border-color: #38bdf8; box-shadow: 0 0 0 2px rgb(56 189 248 / .16); }
        .erp-report-actions { display: flex; gap: 5px; }
        .erp-report-button { display: inline-flex; height: 30px; align-items: center; justify-content: center; border-radius: 5px; padding: 0 11px; font-size: 10px; font-weight: 900; white-space: nowrap; }
        .erp-report-button.is-primary { border: 1px solid #0284c7; background: #0284c7; color: #fff; }
        .erp-report-button.is-secondary { border: 1px solid #cbd5e1; background: #fff; color: #475569; }
        .erp-kpi-strip { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); margin-top: 8px; overflow: hidden; border: 1px solid #dbe3ee; border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgb(15 23 42 / .04); }
        .erp-kpi { min-width: 0; padding: 6px 10px; border-right: 1px solid #e2e8f0; }
        .erp-kpi:last-child { border-right: 0; }
        .erp-kpi-label { color: #64748b; font-size: 8px; font-weight: 900; letter-spacing: .07em; line-height: 1; text-transform: uppercase; }
        .erp-kpi-value { display: block; overflow: hidden; margin-top: 3px; color: #0f172a; font-size: 14px; font-weight: 900; line-height: 1.05; text-overflow: ellipsis; white-space: nowrap; }
        .erp-kpi.is-emerald { background: #f0fdf4; } .erp-kpi.is-emerald .erp-kpi-value { color: #047857; }
        .erp-kpi.is-rose { background: #fff7f7; } .erp-kpi.is-rose .erp-kpi-value { color: #be123c; }
        .erp-kpi.is-violet { background: #faf8ff; } .erp-kpi.is-violet .erp-kpi-value { color: #6d28d9; }
        .erp-kpi.is-sky { background: #f0f9ff; } .erp-kpi.is-sky .erp-kpi-value { color: #0369a1; }
        .erp-kpi.is-amber { background: #fffbeb; } .erp-kpi.is-amber .erp-kpi-value { color: #b45309; }
        .erp-report-grid { margin-top: 8px; overflow: hidden; border-radius: 8px; }
        .erp-report-grid-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; min-height: 29px; padding: 5px 9px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
        .erp-report-grid-title { color: #0f172a; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
        .erp-report-grid-meta { color: #64748b; font-size: 9px; font-weight: 700; }
        .erp-report-scroller { overflow: auto; }
        .erp-report-table { width: 100%; min-width: 1100px; border-collapse: collapse; text-align: left; font-size: 10px; line-height: 1.18; }
        .erp-report-table thead { position: sticky; top: 0; z-index: 1; background: #f8fafc; color: #64748b; }
        .erp-report-table th { height: 27px; padding: 4px 8px; border-bottom: 1px solid #dbe3ee; font-size: 8px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; }
        .erp-report-table td { height: 35px; padding: 4px 8px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        .erp-report-table tbody tr:last-child td { border-bottom: 0; }
        .erp-report-table tbody tr:hover { background: #f8fafc; }
        .erp-primary { display: block; color: #1e293b; font-size: 10px; font-weight: 800; white-space: nowrap; }
        .erp-primary.is-link { color: #0369a1; }
        .erp-secondary { display: block; overflow: hidden; max-width: 210px; margin-top: 1px; color: #64748b; font-size: 8px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .erp-money { font-variant-numeric: tabular-nums; font-weight: 900; white-space: nowrap; }
        .erp-status { display: inline-flex; align-items: center; border-radius: 999px; background: #eef2f7; padding: 2px 6px; color: #475569; font-size: 7px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
        .erp-empty { padding: 28px 10px !important; text-align: center; color: #64748b; font-weight: 700; }
        .erp-pagination { margin-top: 8px; font-size: 10px; }
        @media (max-width: 1100px) {
            .erp-report-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .erp-report-filter-grid .erp-report-search { grid-column: 1 / -1; }
        }
        @media (max-width: 700px) {
            .erp-report-toolbar-top { align-items: stretch; flex-direction: column; }
            .erp-report-scope { justify-content: space-between; }
            .erp-report-filter-grid { grid-template-columns: 1fr; }
            .erp-report-filter-grid .erp-report-search { grid-column: auto; }
            .erp-kpi-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .erp-kpi { border-bottom: 1px solid #e2e8f0; }
        }
    </style>
    @php
        $money = fn (int $minor) => 'INR '.number_format($minor / 100, 2);
        $activeStatuses = $tab === 'payments' ? $paymentStatuses : $bookingStatuses;
    @endphp

    <div class="erp-report-page">
    <section class="erp-report-toolbar">
        <div class="erp-report-toolbar-top">
            <div class="erp-report-tabs" role="tablist" aria-label="Report type">
                <a href="{{ route('admin.reports.index', ['tab' => 'bookings', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="erp-report-tab {{ $tab === 'bookings' ? 'is-active' : '' }}">Booking report</a>
                <a href="{{ route('admin.reports.index', ['tab' => 'payments', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="erp-report-tab {{ $tab === 'payments' ? 'is-active' : '' }}">Payment report</a>
            </div>
            <div class="erp-report-scope" title="Use the property selector in the page header to change this report scope">
                Property scope: <strong>{{ $propertyLabel }}</strong>
                <span>· Header selector</span>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports.index') }}" class="erp-report-filter-grid">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="erp-report-label">From
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="erp-report-control">
            </label>
            <label class="erp-report-label">To
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="erp-report-control">
            </label>
            <label class="erp-report-label">Status
                <select name="status" class="erp-report-control">
                    <option value="">All statuses</option>
                    @foreach($activeStatuses as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <label class="erp-report-label erp-report-search">Search
                <input name="q" value="{{ $search }}" placeholder="Booking, guest or transaction reference" class="erp-report-control">
            </label>
            <div class="erp-report-actions">
                <button class="erp-report-button is-primary">Apply</button>
                <a href="{{ route('admin.reports.index', ['tab' => $tab]) }}" class="erp-report-button is-secondary">Reset</a>
            </div>
        </form>
    </section>

    @if($tab === 'bookings')
        <section class="erp-kpi-strip" aria-label="Booking summary">
            <div class="erp-kpi"><span class="erp-kpi-label">Bookings</span><strong class="erp-kpi-value">{{ number_format($summary['bookings']) }}</strong></div>
            <div class="erp-kpi is-emerald"><span class="erp-kpi-label">Active</span><strong class="erp-kpi-value">{{ number_format($summary['active']) }}</strong></div>
            <div class="erp-kpi is-rose"><span class="erp-kpi-label">Cancelled</span><strong class="erp-kpi-value">{{ number_format($summary['cancelled']) }}</strong></div>
            <div class="erp-kpi is-violet"><span class="erp-kpi-label">Room nights</span><strong class="erp-kpi-value">{{ number_format($summary['room_nights']) }}</strong></div>
            <div class="erp-kpi is-sky"><span class="erp-kpi-label">Booked revenue</span><strong class="erp-kpi-value">{{ $money($summary['revenue_minor']) }}</strong></div>
        </section>

        <section class="erp-report-grid">
            <header class="erp-report-grid-header"><h2 class="erp-report-grid-title">Booking ledger</h2><p class="erp-report-grid-meta">{{ $bookings->total() }} record(s) · {{ $from->format('d M Y') }}–{{ $to->format('d M Y') }}</p></header>
            <div class="erp-report-scroller"><table class="erp-report-table">
                <thead><tr><th>Booking / booked on</th><th>Guest</th><th>Property / room</th><th>Stay</th><th>Source</th><th>Revenue</th><th>Payment</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($bookings as $booking)
                    <tr>
                        <td><a href="{{ route('admin.bookings.show', $booking) }}" class="erp-primary is-link">{{ $booking->booking_number }}</a><span class="erp-secondary">{{ $booking->created_at->format('d M Y, h:i A') }}</span></td>
                        <td><span class="erp-primary">{{ $booking->guest_name }}</span><span class="erp-secondary">{{ $booking->guest_phone ?: $booking->guest_email }}</span></td>
                        <td><span class="erp-primary">{{ $booking->property?->name }}</span><span class="erp-secondary">{{ $booking->room?->room_number ?? 'Unassigned' }} · {{ $booking->roomType?->name }}</span></td>
                        <td><span class="erp-primary">{{ $booking->check_in_date->format('d M') }} → {{ $booking->check_out_date->format('d M Y') }}</span><span class="erp-secondary">{{ $booking->nights }} night(s)</span></td>
                        <td><span class="erp-primary">{{ str($booking->source)->replace('_', ' ')->title() }}</span></td>
                        <td><span class="erp-primary erp-money">{{ $booking->formattedGrossTotal() }}</span>@if($booking->discount_amount_minor)<span class="erp-secondary text-emerald-700">Discount {{ $booking->formattedDiscount() }}</span>@endif</td>
                        <td><span class="erp-primary">{{ $booking->paymentOptionLabel() }}</span><span class="erp-secondary">{{ strtoupper($booking->payment_status) }}@if($booking->outstandingAmountMinor()) · Due {{ $money($booking->outstandingAmountMinor()) }}@endif</span></td>
                        <td><span class="erp-status">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                    </tr>
                @empty<tr><td colspan="8" class="erp-empty">No bookings found for this period and property scope.</td></tr>@endforelse
                </tbody>
            </table></div>
        </section>
        <div class="erp-pagination">{{ $bookings->links() }}</div>
    @else
        <section class="erp-kpi-strip" aria-label="Payment summary">
            <div class="erp-kpi"><span class="erp-kpi-label">Transactions</span><strong class="erp-kpi-value">{{ number_format($summary['transactions']) }}</strong></div>
            <div class="erp-kpi is-emerald"><span class="erp-kpi-label">Collected</span><strong class="erp-kpi-value">{{ $money($summary['collected_minor']) }}</strong></div>
            <div class="erp-kpi is-rose"><span class="erp-kpi-label">Refunded</span><strong class="erp-kpi-value">{{ $money($summary['refunded_minor']) }}</strong></div>
            <div class="erp-kpi is-sky"><span class="erp-kpi-label">Net collection</span><strong class="erp-kpi-value">{{ $money($summary['net_minor']) }}</strong></div>
            <div class="erp-kpi is-amber"><span class="erp-kpi-label">Failed</span><strong class="erp-kpi-value">{{ number_format($summary['failed']) }}</strong></div>
        </section>

        <section class="erp-report-grid">
            <header class="erp-report-grid-header"><h2 class="erp-report-grid-title">Payment ledger</h2><p class="erp-report-grid-meta">{{ $payments->total() }} transaction(s) · {{ $from->format('d M Y') }}–{{ $to->format('d M Y') }}</p></header>
            <div class="erp-report-scroller"><table class="erp-report-table">
                <thead><tr><th>Payment / date</th><th>Booking / guest</th><th>Property</th><th>Mode</th><th>Gateway reference</th><th>Amount</th><th>Refund</th><th>Net</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($payments as $payment)
                    @php
                        $mode = match ($payment->method) {
                            'upi' => 'UPI',
                            'card' => 'Card',
                            'cash' => 'Cash',
                            'bank_transfer' => 'Bank transfer',
                            null => str($payment->gateway)->title()->toString(),
                            default => str($payment->method)->replace('_', ' ')->title()->toString(),
                        };
                        $net = max(0, $payment->amount_minor - $payment->refunded_amount_minor);
                    @endphp
                    <tr>
                        <td><span class="erp-primary">{{ $payment->payment_number }}</span><span class="erp-secondary">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y, h:i A') }}</span></td>
                        <td>@if($payment->booking)<a href="{{ route('admin.bookings.show', $payment->booking) }}" class="erp-primary is-link">{{ $payment->booking->booking_number }}</a><span class="erp-secondary">{{ $payment->booking->guest_name }}</span>@else<span class="erp-primary text-slate-400">Booking unavailable</span>@endif</td>
                        <td><span class="erp-primary">{{ $payment->booking?->property?->name }}</span></td>
                        <td><span class="erp-primary">{{ $mode }}</span><span class="erp-secondary">{{ strtoupper($payment->gateway) }}</span></td>
                        <td><span class="erp-secondary" title="{{ $payment->gateway_payment_id ?: $payment->gateway_order_id }}">{{ $payment->gateway_payment_id ?: $payment->gateway_order_id ?: '—' }}</span></td>
                        <td><span class="erp-primary erp-money">{{ $money($payment->amount_minor) }}</span></td>
                        <td><span class="erp-primary erp-money text-rose-700">{{ $payment->refunded_amount_minor ? $money($payment->refunded_amount_minor) : '—' }}</span></td>
                        <td><span class="erp-primary erp-money text-emerald-700">{{ $money($net) }}</span></td>
                        <td><span class="erp-status">{{ $payment->status }}</span>@if($payment->failure_reason)<span class="erp-secondary text-rose-600" title="{{ $payment->failure_reason }}">{{ Str::limit($payment->failure_reason, 40) }}</span>@endif</td>
                    </tr>
                @empty<tr><td colspan="9" class="erp-empty">No payment transactions found for this period and property scope.</td></tr>@endforelse
                </tbody>
            </table></div>
        </section>
        <div class="erp-pagination">{{ $payments->links() }}</div>
    @endif
    </div>
@endsection
