@extends('public.booking.layout')

@section('title', 'Pay for booking '.$booking->booking_number)

@section('content')
    <div class="confirm-wrap">
        <div class="confirm-card">
            <h2>Complete your payment</h2>
            <p class="addr">Booking {{ $booking->booking_number }} · {{ $booking->property->name }}, {{ $booking->property->city }}</p>

            @if ($errors->any())
                <div class="booking-alert" style="margin-top:16px;">{{ $errors->first() }}</div>
            @endif

            <dl class="confirm-grid">
                <div class="confirm-cell">
                    <dt>Stay</dt>
                    <dd>{{ $booking->check_in_date->format('d M') }} – {{ $booking->check_out_date->format('d M Y') }}</dd>
                    <dd class="sub">{{ $booking->nights }} {{ Str::plural('night', $booking->nights) }} · {{ $groupBookings->count() }} {{ Str::plural('room', $groupBookings->count()) }}</dd>
                </div>
                <div class="confirm-cell">
                    <dt>{{ Str::plural('Room', $groupBookings->count()) }}</dt>
                    @foreach ($groupBookings as $roomBooking)
                        <dd class="sub" style="font-size:13px;">{{ $roomBooking->roomType->name }} · {{ $roomBooking->ratePlan?->name }} · {{ $roomBooking->formattedTotal() }}</dd>
                    @endforeach
                </div>
                <div class="confirm-cell">
                    <dt>Tax breakdown</dt>
                    <dd class="sub">Room tariff: {{ $booking->currency }} {{ number_format($groupBookings->sum('total_amount_minor') / 100, 2) }}</dd>
                    @php
                        $totalDiscount = $groupBookings->sum('discount_amount_minor');
                        $totalTax = $groupBookings->sum('tax_amount_minor');
                    @endphp
                    @if ($totalDiscount > 0)
                        <dd class="sub" style="color:#0a7d33;">Discount ({{ $groupBookings->pluck('discount_label')->filter()->unique()->implode(', ') }}): − {{ $booking->currency }} {{ number_format($totalDiscount / 100, 2) }}</dd>
                    @endif
                    <dd class="sub">CGST: {{ $booking->currency }} {{ number_format(intdiv($totalTax, 2) / 100, 2) }} · SGST: {{ $booking->currency }} {{ number_format(($totalTax - intdiv($totalTax, 2)) / 100, 2) }}</dd>
                    <dd class="sub">GST @ {{ \App\Services\Payments\Gst::ratePercent($booking->tax_rate_bp) }}%</dd>
                </div>
                <div class="confirm-cell">
                    <dt>Amount payable</dt>
                    <dd>{{ $payment->formattedAmount() }}</dd>
                    <dd class="sub">{{ $localSandbox ? 'Local development payment sandbox' : 'Secure payment via Razorpay (test mode)' }}</dd>
                </div>
            </dl>

            @if ($localSandbox)
                <div class="booking-alert" style="margin-top:18px;border-color:#f4c95d;background:#fff9df;color:#6b4b00;">
                    <strong>Development sandbox</strong> — no money will be charged and no request will be sent to Razorpay.
                </div>
            @endif

            <div class="confirm-actions">
                @if ($localSandbox)
                    <button class="btn-reserve" type="submit" form="verifyForm">Simulate successful payment</button>
                @else
                    <button id="payButton" class="btn-reserve" type="button">Pay {{ $payment->formattedAmount() }}</button>
                @endif
                <a href="{{ route('book.confirmation', ['bookingNumber' => $booking->booking_number]) }}" class="ghost">Pay at the property instead</a>
            </div>
        </div>
    </div>

    <form id="verifyForm" method="POST" action="{{ route('book.pay.verify') }}" hidden>
        @csrf
        <input type="hidden" name="razorpay_order_id" value="{{ $payment->gateway_order_id }}">
        <input type="hidden" name="razorpay_payment_id" value="{{ $sandboxPaymentId }}">
        <input type="hidden" name="razorpay_signature" value="{{ $localSandbox ? hash_hmac('sha256', $payment->gateway_order_id.'|'.$sandboxPaymentId, config('services.razorpay.key_secret')) : '' }}">
    </form>
@endsection

@section('scripts')
    @unless ($localSandbox)
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.getElementById('payButton').addEventListener('click', function () {
            const razorpay = new Razorpay({
                key: @json($razorpayKey),
                amount: @json($payment->amount_minor),
                currency: @json($payment->currency),
                name: @json($booking->property->name),
                description: @json('Booking '.$booking->booking_number),
                order_id: @json($payment->gateway_order_id),
                prefill: {
                    name: @json($booking->guest_name),
                    email: @json($booking->guest_email),
                    contact: @json($booking->guest_phone),
                },
                theme: {color: '#ffc40d'},
                handler: function (response) {
                    const form = document.getElementById('verifyForm');
                    form.querySelector('[name="razorpay_payment_id"]').value = response.razorpay_payment_id;
                    form.querySelector('[name="razorpay_signature"]').value = response.razorpay_signature;
                    form.submit();
                },
            });
            razorpay.open();
        });
    </script>
    @endunless
@endsection
