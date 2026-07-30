<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $bookings = Booking::query()
            ->with(['property', 'roomType', 'room', 'ratePlan'])
            ->where(function ($query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->orWhere(fn ($sub) => $user->email ? $sub->where('guest_email', $user->email) : $sub->whereRaw('1 = 0'));
            })
            ->orderByDesc('check_in_date')
            ->orderByDesc('id')
            ->get();

        // MMT-style trip buckets. A stay counts as upcoming until its
        // check-out date has passed (so an in-house stay is still "upcoming").
        [$cancelled, $active] = $bookings->partition(
            fn (Booking $booking) => $booking->status === Booking::STATUS_CANCELLED
        );
        [$completed, $upcoming] = $active->partition(
            fn (Booking $booking) => $booking->status === Booking::STATUS_CHECKED_OUT
                || $booking->check_out_date->lt(now()->startOfDay())
        );

        // "Cart" — reservations that are saved but still awaiting online payment
        // (e.g. the gateway failed or the guest abandoned checkout). They can be
        // resumed from here.
        $pendingPayment = $upcoming->filter(
            fn (Booking $booking) => $booking->payment_status === Booking::PAYMENT_UNPAID
                && in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED], true)
        )->sortBy('check_in_date')->values();

        return view('customer.dashboard', [
            'bookings' => $bookings,
            'upcoming' => $upcoming->sortBy('check_in_date')->values(),
            'completed' => $completed->values(),
            'cancelled' => $cancelled->values(),
            'pendingPayment' => $pendingPayment,
            'totalNights' => $active->sum('nights'),
        ]);
    }
}
