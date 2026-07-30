@extends('admin.layouts.app')

@section('title', $booking->booking_number)
@section('eyebrow', 'Booking Profile')
@section('page-title', $booking->booking_number)

@section('header-actions')
    <a href="{{ route('admin.bookings.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">All Bookings</a>
    @if (! in_array($booking->status, ['cancelled', 'checked_out'], true))
        <a href="{{ route('admin.bookings.stay', $booking) }}" class="inline-flex h-10 items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700 transition shadow-sm">{{ $booking->status === 'checked_in' ? 'Manage Stay' : 'Prepare Check-in' }}</a>
    @endif
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
                    <p class="mt-2 text-sm font-semibold text-slate-500">
                        {{ $booking->roomType->name }} / {{ $booking->room ? 'Room '.$booking->room->room_number : 'Room not assigned yet' }}
                        @if ($booking->ratePlan)
                            <span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-black ring-1 {{ $booking->ratePlan->meal_plan === 'ep' ? 'bg-slate-100 text-slate-600 ring-slate-200' : 'bg-amber-50 text-amber-800 ring-amber-200' }}" title="{{ $booking->ratePlan->name }}">
                                {{ strtoupper($booking->ratePlan->meal_plan) }}{{ $booking->ratePlan->meal_plan !== 'ep' ? ' 🍳' : '' }}
                            </span>
                        @endif
                    </p>
                </div>
                <span class="w-fit rounded-full bg-slate-950 px-3 py-1 text-xs font-black uppercase tracking-wide text-white">{{ str_replace('_', ' ', $booking->status) }}</span>
            </div>

            <div class="mt-6 grid gap-3 md:grid-cols-4">
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Check-in</p><p class="mt-2 text-xl font-black">{{ $booking->check_in_date->format('M j, Y') }}</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Check-out</p><p class="mt-2 text-xl font-black">{{ $booking->check_out_date->format('M j, Y') }}</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Nights</p><p class="mt-2 text-xl font-black">{{ $booking->nights }}</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">{{ $groupBookings->count() > 1 ? 'This room' : 'Total' }}</p><p class="mt-2 text-xl font-black">{{ $booking->formattedTotal() }}</p></div>
            </div>

            <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-black">Payment & GST</h3>
                    <div class="flex items-center gap-2">
                        @if ($booking->billing === \App\Models\Booking::BILLING_CORPORATE)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">Bill to company{{ $booking->corporate ? ' · '.$booking->corporate->displayName() : '' }}</span>
                        @elseif ($booking->corporate)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Corporate · {{ $booking->corporate->displayName() }}</span>
                        @endif
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $booking->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($booking->payment_status === 'refunded' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ ucfirst($booking->payment_status ?: 'unpaid') }}</span>
                    </div>
                </div>
                <div class="{{ $booking->discount_amount_minor > 0 ? 'mt-3 grid gap-3 text-sm md:grid-cols-4' : 'mt-3 grid gap-3 text-sm md:grid-cols-3' }}">
                    <div><p class="text-xs font-bold text-slate-500">Room tariff</p><p class="font-black">{{ $booking->formattedTotal() }}</p></div>
                    @if ($booking->discount_amount_minor > 0)
                        <div><p class="text-xs font-bold text-slate-500">Discount{{ $booking->discount_label ? ' · '.$booking->discount_label : '' }}</p><p class="font-black text-emerald-700">− {{ $booking->formattedDiscount() }}</p></div>
                    @endif
                    <div><p class="text-xs font-bold text-slate-500">GST @ {{ \App\Services\Payments\Gst::ratePercent($booking->tax_rate_bp) }}%</p><p class="font-black">{{ $booking->currency }} {{ number_format($booking->tax_amount_minor / 100, 2) }}</p><p class="text-[11px] font-semibold text-slate-500">CGST {{ number_format(intdiv($booking->tax_amount_minor, 2) / 100, 2) }} · SGST {{ number_format(($booking->tax_amount_minor - intdiv($booking->tax_amount_minor, 2)) / 100, 2) }}</p></div>
                    <div><p class="text-xs font-bold text-slate-500">Payable{{ $booking->invoice_number ? ' · Invoice '.$booking->invoice_number : '' }}</p><p class="font-black">{{ $booking->formattedGrossTotal() }}</p></div>
                </div>
                @if($payments->isNotEmpty())
                    <div class="mt-3 space-y-1 border-t border-slate-100 pt-3">
                        @foreach($payments as $payment)
                            <p class="text-xs font-semibold text-slate-600">
                                {{ $payment->payment_number }} · {{ ucfirst($payment->status) }} · {{ $payment->formattedAmount() }}
                                @if($payment->gateway_payment_id) · {{ $payment->gateway }} {{ $payment->gateway_payment_id }} @endif
                                @if($payment->paid_at) · paid {{ $payment->paid_at->format('d M Y H:i') }} @endif
                                @if($payment->refunded_amount_minor) · refunded {{ number_format($payment->refunded_amount_minor / 100, 2) }} @endif
                            </p>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($groupBookings->count() > 1)
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50/60 p-4"><div class="flex items-center justify-between"><div><h3 class="font-black text-blue-950">Multi-room booking</h3><p class="text-xs font-semibold text-blue-700">{{ $groupBookings->count() }} linked room reservations for this guest</p></div><span class="rounded bg-blue-100 px-2 py-1 text-[10px] font-black text-blue-700">GROUP</span></div><div class="mt-3 flex flex-wrap gap-2">@foreach($groupBookings as $linkedBooking)<a href="{{ route('admin.bookings.show',$linkedBooking) }}" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-800">Room {{ $linkedBooking->room?->room_number ?: 'unassigned' }} · {{ $linkedBooking->booking_number }} · {{ $linkedBooking->formattedTotal() }}</a>@endforeach</div><p class="mt-3 text-sm font-black text-blue-950">Group total: {{ $booking->currency }} {{ number_format($groupBookings->sum('total_amount_minor') / 100, 2) }}</p></div>
            @endif

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

            <div class="mt-4 rounded-lg border border-slate-200 p-4">
                <div class="flex items-center justify-between"><h3 class="font-black">Registered occupants</h3><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-black text-slate-600">{{ $booking->guests->where('is_staying', true)->count() }}</span></div>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($booking->guests->where('is_staying', true) as $guest)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"><span><strong class="block text-sm">{{ $guest->full_name }}</strong><small class="text-slate-500">{{ ucfirst($guest->role) }} · {{ ucfirst($guest->guest_type) }}</small></span><span class="text-xs font-bold {{ $guest->id_verification_status === 'verified' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $guest->id_verification_status === 'verified' ? '✓ ID verified' : 'ID pending' }}</span></div>
                    @endforeach
                </div>
            </div>
        </article>

        <aside class="space-y-6">
            @if (! $booking->room_id && ! in_array($booking->status, ['cancelled', 'checked_out'], true))
                <section class="rounded-lg border border-sky-200 bg-sky-50 p-5 shadow-sm">
                    <h2 class="text-lg font-black text-sky-950">Assign Room</h2>
                    <p class="mt-1 text-xs font-semibold text-sky-800">Pick the physical room at check-in. Only rooms free for the whole stay are listed.</p>
                    @if ($assignableRooms->isEmpty())
                        <p class="mt-3 rounded-lg bg-white px-3 py-2 text-sm font-bold text-rose-700">No free {{ $booking->roomType->name }} rooms for these dates.</p>
                    @else
                        <form method="POST" action="{{ route('admin.bookings.assign-room', $booking) }}" class="mt-3 flex gap-2">
                            @csrf
                            <select name="room_id" required class="h-10 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold">
                                @foreach ($assignableRooms as $room)
                                    <option value="{{ $room->id }}">Room {{ $room->room_number }}{{ $room->floor ? ' · '.$room->floor : '' }}</option>
                                @endforeach
                            </select>
                            <button class="h-10 rounded-lg bg-sky-600 px-4 text-sm font-black text-white hover:bg-sky-700">Assign</button>
                        </form>
                        @error('room_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                    @endif
                </section>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Requests</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $booking->special_requests ?: 'No special requests.' }}</p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Internal Notes</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $booking->internal_notes ?: 'No internal notes.' }}</p>
            </section>

            @if ($booking->status !== 'cancelled')
                <details class="rounded-lg border border-rose-200 bg-rose-50 p-5" @if($errors->hasAny(['cancellation_reason', 'reason_note', 'refund_override'])) open @endif>
                    <summary class="cursor-pointer text-lg font-black text-rose-950">Cancel Booking</summary>
                    <p class="mt-2 text-sm font-semibold text-rose-700">Cancellation releases the room for future availability checks.</p>

                    @if ($cancelQuote && $cancelQuote['paid_minor'] > 0)
                        <div class="mt-3 rounded-lg bg-white p-3 text-sm font-semibold text-slate-700">
                            <p>Paid online: <strong>{{ $booking->currency }} {{ number_format($cancelQuote['paid_minor'] / 100, 2) }}</strong></p>
                            <p class="mt-1">Per policy right now: <strong>{{ $cancelQuote['refund_percent'] }}% refund = {{ $booking->currency }} {{ number_format($cancelQuote['refund_due_minor'] / 100, 2) }}</strong>
                                @if ($cancelQuote['fee_minor'] > 0)
                                    <span class="text-rose-700">(fee {{ $booking->currency }} {{ number_format($cancelQuote['fee_minor'] / 100, 2) }})</span>
                                @endif
                            </p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('DELETE')
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-rose-900">Reason *</label>
                            <select name="cancellation_reason" required class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold">
                                <option value="">Select a reason…</option>
                                @foreach ($cancellationReasons as $value => $label)
                                    <option value="{{ $value }}" @selected(old('cancellation_reason') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('cancellation_reason')<p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-rose-900">Note (optional)</label>
                            <input name="reason_note" value="{{ old('reason_note') }}" maxlength="255" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold" placeholder="Anything worth remembering">
                        </div>
                        @if ($cancelQuote && $cancelQuote['paid_minor'] > 0 && auth()->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN))
                            <div>
                                <label class="text-xs font-black uppercase tracking-wide text-rose-900">Override refund amount ({{ $booking->currency }}, optional)</label>
                                <input type="number" name="refund_override" step="0.01" min="0" max="{{ number_format($cancelQuote['paid_minor'] / 100, 2, '.', '') }}" value="{{ old('refund_override') }}" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold" placeholder="Leave empty to follow the policy">
                                <p class="mt-1 text-[11px] font-semibold text-slate-500">Super admin only — the override is recorded in the activity log.</p>
                                @error('refund_override')<p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        @endif
                        <button class="h-10 rounded-lg bg-rose-700 px-4 text-sm font-bold text-white" onclick="return confirm('Cancel this booking?')">Cancel Booking</button>
                    </form>
                </details>
            @endif
        </aside>
    </section>
@endsection
