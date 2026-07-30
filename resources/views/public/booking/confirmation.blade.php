@extends('public.booking.layout')

@section('title', 'Booking '.$booking->booking_number)

@section('content')
    <div class="confirm-wrap">
        @if (session('status'))
            <div class="confirm-hero" style="margin-bottom:20px;">
                <p style="margin:0;color:#1d4620;font-weight:700;">{{ session('status') }}</p>
            </div>
        @endif
        @if (session('payment_error'))
            <div class="booking-alert" style="margin-bottom:20px;">{{ session('payment_error') }} Your reservation is saved — you can pay at the property.</div>
        @endif

        <div class="confirm-hero" @if($booking->status === \App\Models\Booking::STATUS_CANCELLED) style="background:#fdf1f1;border-color:#efc9c9;" @endif>
            @if ($booking->status === \App\Models\Booking::STATUS_CANCELLED)
                <p class="tick" style="color:#a33030;"><i class="fa-solid fa-circle-xmark"></i></p>
                <h1 style="color:#7c2626;">Reservation cancelled</h1>
                <p style="color:#a33030;">Reference <span class="ref" style="color:#7c2626;">{{ $booking->booking_number }}</span></p>
            @else
                <p class="tick"><i class="fa-solid fa-circle-check"></i></p>
                <h1>{{ $booking->payment_status === \App\Models\Booking::PAYMENT_PAID ? 'Booking confirmed!' : 'Reservation received!' }}</h1>
                <p>Your booking reference is <span class="ref">{{ $booking->booking_number }}</span></p>
                @if ($groupBookings->count() > 1)
                    <p>{{ $groupBookings->count() }} rooms reserved together under this reference.</p>
                @endif
                @if ($booking->payment_status === \App\Models\Booking::PAYMENT_PAID)
                    <p>Paid online · confirmed instantly. A record of your payment is below.</p>
                @else
                    <p>Status: pending confirmation by the property. You'll be contacted on {{ $booking->guest_phone }}.</p>
                @endif
            @endif
        </div>

        <div class="confirm-card">
            <h2>{{ $booking->property->name }}</h2>
            <p class="addr">{{ $booking->property->address }}, {{ $booking->property->city }}@if($booking->property->gstin) · GSTIN: {{ $booking->property->gstin }}@endif</p>

            <dl class="confirm-grid">
                <div class="confirm-cell">
                    <dt>Check-in</dt>
                    <dd>{{ $booking->check_in_date->format('D, d M Y') }}</dd>
                </div>
                <div class="confirm-cell">
                    <dt>Check-out</dt>
                    <dd>{{ $booking->check_out_date->format('D, d M Y') }}</dd>
                </div>
                <div class="confirm-cell">
                    <dt>{{ Str::plural('Room', $groupBookings->count()) }}</dt>
                    @foreach ($groupBookings as $roomBooking)
                        <dd>{{ $roomBooking->roomType->name }}</dd>
                        <dd class="sub">{{ $roomBooking->ratePlan?->name }} · {{ $roomBooking->formattedTotal() }} + {{ \App\Services\Payments\Gst::ratePercent($roomBooking->tax_rate_bp) }}% GST</dd>
                    @endforeach
                    <dd class="sub">{{ $booking->adults }} adults{{ $booking->children ? ', '.$booking->children.' children' : '' }} in total</dd>
                </div>
                <div class="confirm-cell">
                    <dt>Amount ({{ $booking->nights }} {{ Str::plural('night', $booking->nights) }})</dt>
                    @php
                        $tariff = $groupBookings->sum('total_amount_minor');
                        $discount = $groupBookings->sum('discount_amount_minor');
                        $tax = $groupBookings->sum('tax_amount_minor');
                        $discountLabels = $groupBookings->pluck('discount_label')->filter()->unique()->implode(', ');
                    @endphp
                    <dd class="sub">Room tariff: {{ $booking->currency }} {{ number_format($tariff / 100, 2) }}</dd>
                    @if ($discount > 0)
                        <dd class="sub" style="color:#0a7d33;">Discount{{ $discountLabels ? ' ('.$discountLabels.')' : '' }}: − {{ $booking->currency }} {{ number_format($discount / 100, 2) }}</dd>
                    @endif
                    <dd class="sub">CGST: {{ $booking->currency }} {{ number_format(intdiv($tax, 2) / 100, 2) }} · SGST: {{ $booking->currency }} {{ number_format(($tax - intdiv($tax, 2)) / 100, 2) }}</dd>
                    <dd>{{ $booking->currency }} {{ number_format(($tariff - $discount + $tax) / 100, 2) }}</dd>
                    <dd class="sub">
                        @if ($booking->billing === \App\Models\Booking::BILLING_CORPORATE)
                            Billed to {{ $booking->corporate?->displayName() ?? 'your company' }}
                        @else
                            {{ $booking->payment_status === \App\Models\Booking::PAYMENT_PAID ? 'Paid online' : ($booking->payment_status === \App\Models\Booking::PAYMENT_REFUNDED ? 'Refunded' : 'Pay at the property') }}
                        @endif
                    </dd>
                </div>
                @if ($booking->status !== \App\Models\Booking::STATUS_CANCELLED)
                    <div class="confirm-cell" style="grid-column:1 / -1;">
                        <dt>Cancellation policy</dt>
                        @foreach ($policyLines as $line)
                            <dd class="sub">{{ $line }}</dd>
                        @endforeach
                    </div>
                @endif
                @if ($payment)
                    <div class="confirm-cell" style="grid-column:1 / -1;">
                        <dt>Payment record</dt>
                        <dd class="sub">{{ $payment->payment_number }} · Razorpay {{ $payment->gateway_payment_id }} · {{ $payment->formattedAmount() }} · {{ $payment->paid_at?->format('d M Y, H:i') }}</dd>
                        @if ($booking->invoice_number)
                            <dd class="sub">Invoice no: {{ $booking->invoice_number }}</dd>
                        @endif
                        @if ($payment->status === \App\Models\Payment::STATUS_REFUNDED)
                            <dd class="sub">Refunded {{ $booking->currency }} {{ number_format($payment->refunded_amount_minor / 100, 2) }} on {{ $payment->refunded_at?->format('d M Y') }} (ref {{ $payment->gateway_refund_id }})</dd>
                        @endif
                    </div>
                @endif
            </dl>
        </div>

        <div class="confirm-actions">
            <a href="{{ route('book.search') }}" class="ghost">Book another stay</a>
            @if ($booking->status !== \App\Models\Booking::STATUS_CANCELLED && $booking->payment_status === \App\Models\Booking::PAYMENT_UNPAID && $booking->status === \App\Models\Booking::STATUS_PENDING)
                <a href="{{ route('book.pay', ['bookingNumber' => $booking->booking_number]) }}" class="btn-reserve" style="text-decoration:none;">Pay online now</a>
            @endif
            @if (! in_array($booking->status, [\App\Models\Booking::STATUS_CANCELLED, \App\Models\Booking::STATUS_CHECKED_IN, \App\Models\Booking::STATUS_CHECKED_OUT], true))
                <a href="{{ route('book.cancel', ['bookingNumber' => $booking->booking_number]) }}" class="ghost" style="color:#a33030;border-color:#e5b9b9;">Cancel booking</a>
            @endif
            @auth
                <a href="{{ route('customer.dashboard') }}" class="ghost">View my bookings</a>
            @endauth
        </div>
    </div>
@endsection
