@extends('public.booking.layout')

@section('title', 'Cancel booking '.$booking->booking_number)

@section('content')
    <div class="confirm-wrap">
        <div class="confirm-card">
            <h2>Cancel reservation {{ $booking->booking_number }}</h2>
            <p class="addr">{{ $booking->property->name }} · {{ $booking->check_in_date->format('d M') }} – {{ $booking->check_out_date->format('d M Y') }} · {{ $groupBookings->count() }} {{ Str::plural('room', $groupBookings->count()) }}</p>

            @if ($errors->any())
                <div class="booking-alert" style="margin-top:16px;">{{ $errors->first() }}</div>
            @endif

            <dl class="confirm-grid">
                <div class="confirm-cell">
                    <dt>Cancellation policy</dt>
                    @foreach ($policyLines as $line)
                        <dd class="sub">{{ $line }}</dd>
                    @endforeach
                </div>
                <div class="confirm-cell">
                    <dt>Payment status</dt>
                    <dd>{{ ucfirst($booking->payment_status) }}</dd>
                    <dd class="sub">Total {{ $booking->currency }} {{ number_format($groupBookings->sum(fn ($room) => $room->grossTotalMinor()) / 100, 2) }} incl. GST</dd>
                </div>
                @if ($quote['paid_minor'] > 0)
                    <div class="confirm-cell">
                        <dt>If you cancel now</dt>
                        <dd><strong>{{ $booking->currency }} {{ number_format($quote['refund_due_minor'] / 100, 2) }}</strong> refunded ({{ $quote['refund_percent'] }}% of {{ $booking->currency }} {{ number_format($quote['paid_minor'] / 100, 2) }} paid)</dd>
                        @if ($quote['fee_minor'] > 0)
                            <dd class="sub">A cancellation fee of {{ $booking->currency }} {{ number_format($quote['fee_minor'] / 100, 2) }} applies as per the policy above.</dd>
                        @else
                            <dd class="sub">Refunds reach your original payment method in 5–7 working days.</dd>
                        @endif
                    </div>
                @endif
            </dl>

            <form method="POST" action="{{ route('book.cancel.store', ['bookingNumber' => $booking->booking_number]) }}" class="guest-card__body" style="padding:20px 0 0;">
                @csrf
                <div>
                    <label>Confirm the phone number on the booking *</label>
                    <input name="guest_phone" required placeholder="Mobile number used while booking">
                </div>
                <div class="full confirm-actions" style="justify-content:flex-start;">
                    <button type="submit" class="btn-reserve" style="background:#c0392b;color:#fff;">Cancel this reservation</button>
                    <a href="{{ route('book.confirmation', ['bookingNumber' => $booking->booking_number]) }}" class="ghost">Keep my booking</a>
                </div>
            </form>
        </div>
    </div>
@endsection
