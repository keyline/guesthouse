@extends('public.booking.layout')

@section('title', 'My Trips')

@section('content')
    <div class="account-head">
        <div>
            <p class="account-head__eyebrow">My Account</p>
            <h1>Hi, {{ auth()->user()->name }} 👋</h1>
            <p class="sub">All your stays in one place — manage, pay, or cancel anytime.</p>
        </div>
        <div class="account-head__actions">
            <a href="{{ route('book.search') }}" class="btn-reserve">Book a stay</a>
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button class="account-logout">Logout</button>
            </form>
        </div>
    </div>

    <div class="account-stats">
        <div class="account-stat">
            <i class="fa-solid fa-suitcase-rolling"></i>
            <div><strong>{{ $bookings->count() }}</strong><span>Total bookings</span></div>
        </div>
        <div class="account-stat">
            <i class="fa-solid fa-moon"></i>
            <div><strong>{{ $totalNights }}</strong><span>Nights stayed &amp; booked</span></div>
        </div>
        <div class="account-stat">
            <i class="fa-solid fa-calendar-check"></i>
            <div><strong>{{ $upcoming->count() }}</strong><span>Upcoming trips</span></div>
        </div>
    </div>

    @if ($pendingPayment->isNotEmpty())
        <section class="pay-pending">
            <div class="pay-pending__head">
                <i class="fa-solid fa-hourglass-half"></i>
                <div>
                    <h2>Complete your payment</h2>
                    <p>{{ $pendingPayment->count() }} {{ Str::plural('reservation', $pendingPayment->count()) }} saved but awaiting payment. Pay now to confirm instantly — or they’ll stay pending.</p>
                </div>
            </div>
            <ul class="pay-pending__list">
                @foreach ($pendingPayment as $booking)
                    <li class="pay-pending__item">
                        <div class="pay-pending__info">
                            <strong>{{ $booking->property?->name }}</strong>
                            <span>{{ $booking->roomType?->name }} · {{ $booking->check_in_date->format('D, d M') }} → {{ $booking->check_out_date->format('D, d M Y') }}</span>
                            <span class="pay-pending__ref">Booking ID: {{ $booking->booking_number }}</span>
                        </div>
                        <div class="pay-pending__action">
                            <span class="pay-pending__amount">{{ $booking->currency }} {{ number_format($booking->grossTotalMinor() / 100, 2) }}</span>
                            <a href="{{ route('book.pay', ['bookingNumber' => $booking->booking_number]) }}" class="btn-reserve pay-pending__btn">Pay now</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="account-tabs" role="tablist">
        <button type="button" class="active" data-trip-tab="upcoming">Upcoming <span class="count">{{ $upcoming->count() }}</span></button>
        <button type="button" data-trip-tab="completed">Completed <span class="count">{{ $completed->count() }}</span></button>
        <button type="button" data-trip-tab="cancelled">Cancelled <span class="count">{{ $cancelled->count() }}</span></button>
    </div>

    @foreach (['upcoming' => $upcoming, 'completed' => $completed, 'cancelled' => $cancelled] as $bucket => $trips)
        <div class="trip-list" data-trip-panel="{{ $bucket }}" @if($bucket !== 'upcoming') hidden @endif>
            @forelse ($trips as $booking)
                <article class="trip-card">
                    <div class="trip-card__date">
                        <span class="mon">{{ $booking->check_in_date->format('M') }}</span>
                        <span class="day">{{ $booking->check_in_date->format('d') }}</span>
                        <span class="yr">{{ $booking->check_in_date->format('Y') }}</span>
                    </div>
                    <div class="trip-card__body">
                        <h3>
                            {{ $booking->property->name }}
                            <span class="status status--{{ $booking->status }}">{{ \App\Models\Booking::statusLabels()[$booking->status] ?? ucfirst($booking->status) }}</span>
                        </h3>
                        <p class="trip-card__meta">
                            <i class="fa-solid fa-bed"></i>{{ $booking->roomType->name }}{{ $booking->room ? ' · Room '.$booking->room->room_number : '' }}
                            <span class="dot">•</span>
                            <i class="fa-regular fa-calendar"></i>{{ $booking->check_in_date->format('D, d M') }} → {{ $booking->check_out_date->format('D, d M Y') }}
                            <span class="dot">•</span>
                            {{ $booking->nights }} {{ Str::plural('night', $booking->nights) }} · {{ $booking->adults }} {{ Str::plural('adult', $booking->adults) }}{{ $booking->children ? ' + '.$booking->children.' '.Str::plural('child', $booking->children) : '' }}
                        </p>
                        <p class="trip-card__ref">Booking ID: {{ $booking->booking_number }}{{ $booking->ratePlan ? ' · '.(\App\Models\RatePlan::mealPlans()[$booking->ratePlan->meal_plan] ?? strtoupper($booking->ratePlan->meal_plan)) : '' }}</p>
                    </div>
                    <div class="trip-card__side">
                        <div class="trip-card__price">
                            {{ $booking->currency }} {{ number_format($booking->grossTotalMinor() / 100, 2) }}
                            <small>
                                @if ($booking->payment_status === \App\Models\Booking::PAYMENT_PAID) Paid online
                                @elseif ($booking->payment_status === \App\Models\Booking::PAYMENT_REFUNDED) Refunded
                                @elseif ($booking->status === \App\Models\Booking::STATUS_CANCELLED) —
                                @else Pay at the property
                                @endif
                            </small>
                        </div>
                        <div class="trip-card__actions">
                            @if ($booking->source === \App\Models\Booking::SOURCE_ONLINE)
                                @if ($bucket === 'upcoming' && $booking->status === \App\Models\Booking::STATUS_PENDING && $booking->payment_status === \App\Models\Booking::PAYMENT_UNPAID)
                                    <a href="{{ route('book.pay', ['bookingNumber' => $booking->booking_number]) }}" class="pay">Pay now</a>
                                @endif
                                <a href="{{ route('book.confirmation', ['bookingNumber' => $booking->booking_number]) }}" class="manage">{{ $bucket === 'upcoming' ? 'Manage' : 'View' }}</a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="trip-empty">
                    @if ($bucket === 'upcoming')
                        <i class="fa-solid fa-umbrella-beach"></i>
                        <p>No upcoming trips — time to plan your next stay!</p>
                        <a href="{{ route('book.search') }}" class="btn-reserve">Explore rooms</a>
                    @elseif ($bucket === 'completed')
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <p>Your completed stays will appear here.</p>
                    @else
                        <i class="fa-regular fa-face-smile"></i>
                        <p>No cancelled bookings — great!</p>
                    @endif
                </div>
            @endforelse
        </div>
    @endforeach
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-trip-tab]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('[data-trip-tab]').forEach(t => t.classList.toggle('active', t === tab));
                document.querySelectorAll('[data-trip-panel]').forEach(p => p.hidden = p.dataset.tripPanel !== tab.dataset.tripTab);
            });
        });
    </script>
@endsection
