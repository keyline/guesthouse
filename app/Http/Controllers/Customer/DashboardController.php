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
            ->with(['property', 'roomType', 'room'])
            ->where(function ($query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->orWhere('guest_email', $user->email);
            })
            ->orderByDesc('check_in_date')
            ->get();

        return view('customer.dashboard', [
            'bookings' => $bookings,
        ]);
    }
}
