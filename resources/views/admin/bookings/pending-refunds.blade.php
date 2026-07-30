@extends('admin.layouts.app')

@section('title', 'Pending Refunds')
@section('eyebrow', 'Booking Desk')
@section('page-title', 'Pending Refunds')

@section('header-actions')
    <a href="{{ route('admin.bookings.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">All Bookings</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <p class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">
        These cancellations owe the guest money that the gateway could not (or cannot) refund automatically.
        Pay the guest by their original method or bank transfer, then mark the refund settled.
    </p>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Booking</th>
                        <th class="px-5 py-3">Guest</th>
                        <th class="px-5 py-3">Property</th>
                        <th class="px-5 py-3">Cancelled</th>
                        <th class="px-5 py-3">Refund due</th>
                        <th class="px-5 py-3">Why manual</th>
                        <th class="px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bookings as $booking)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-black">{{ $booking->booking_number }}</td>
                            <td class="px-5 py-4">
                                <span class="block font-bold">{{ $booking->guest_name }}</span>
                                <span class="text-xs font-semibold text-slate-500">{{ $booking->guest_phone ?: $booking->guest_email }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $booking->property->name }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $booking->cancelled_at?->format('M j, Y H:i') }}
                                <span class="block text-xs font-semibold text-slate-500">by {{ $booking->cancelled_by ?: '—' }}{{ $booking->cancellation_reason ? ' · '.$booking->cancellation_reason : '' }}</span>
                            </td>
                            <td class="px-5 py-4 font-black text-rose-700">{{ $booking->currency }} {{ number_format($booking->refund_due_minor / 100, 2) }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-black text-amber-800">{{ $booking->refund_state === 'failed' ? 'Gateway refund failed' : 'No gateway payment' }}</span></td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="font-bold text-slate-900">Open</a>
                                    <form method="POST" action="{{ route('admin.bookings.settle-refund', $booking) }}">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-black text-white hover:bg-emerald-700 transition" onclick="return confirm('Confirm the guest has received {{ $booking->currency }} {{ number_format($booking->refund_due_minor / 100, 2) }}?')">Mark settled</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center">
                                <h2 class="text-xl font-black">Nothing pending 🎉</h2>
                                <p class="mt-2 text-sm font-semibold text-slate-500">Every cancelled booking's refund has been settled.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $bookings->links() }}</div>
@endsection
