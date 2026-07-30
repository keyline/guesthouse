<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Property;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $validated = $request->validate([
            'tab' => ['nullable', 'in:bookings,payments'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $tab = $validated['tab'] ?? 'bookings';
        $from = CarbonImmutable::parse($validated['from'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($validated['to'] ?? now()->toDateString())->endOfDay();
        $status = $validated['status'] ?? null;
        $search = trim($validated['q'] ?? '');
        $selectedPropertyId = $scope->selectedPropertyId();
        $propertyLabel = $selectedPropertyId
            ? Property::query()->whereKey($selectedPropertyId)->value('name') ?? 'Selected property'
            : 'All properties';

        $bookings = null;
        $payments = null;
        $summary = [];

        if ($tab === 'payments') {
            $query = Payment::query()
                ->with(['booking.property'])
                ->whereBetween(DB::raw('COALESCE(payments.paid_at, payments.created_at)'), [$from, $to])
                ->when($selectedPropertyId, fn (Builder $builder) => $builder->whereHas('booking', fn (Builder $booking) => $booking->where('property_id', $selectedPropertyId)))
                ->when($status, fn (Builder $builder) => $builder->where('status', $status))
                ->when($search, fn (Builder $builder) => $builder->where(function (Builder $nested) use ($search): void {
                    $nested->where('payment_number', 'like', "%{$search}%")
                        ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                        ->orWhere('gateway_order_id', 'like', "%{$search}%")
                        ->orWhereHas('booking', fn (Builder $booking) => $booking
                            ->where('booking_number', 'like', "%{$search}%")
                            ->orWhere('guest_name', 'like', "%{$search}%"));
                }));

            $grossCollected = (int) (clone $query)
                ->whereIn('status', [Payment::STATUS_CAPTURED, Payment::STATUS_REFUNDED])
                ->sum('amount_minor');
            $refunded = (int) (clone $query)->sum('refunded_amount_minor');
            $summary = [
                'transactions' => (clone $query)->count(),
                'collected_minor' => $grossCollected,
                'refunded_minor' => $refunded,
                'net_minor' => max(0, $grossCollected - $refunded),
                'failed' => (clone $query)->where('status', Payment::STATUS_FAILED)->count(),
            ];

            $payments = $query
                ->orderByRaw('COALESCE(payments.paid_at, payments.created_at) DESC')
                ->latest('payments.id')
                ->paginate(25)
                ->withQueryString();
        } else {
            $query = $scope->apply(Booking::query())
                ->with(['property', 'roomType', 'room', 'payments'])
                ->whereBetween('bookings.created_at', [$from, $to])
                ->when($status, fn (Builder $builder) => $builder->where('status', $status))
                ->when($search, fn (Builder $builder) => $builder->where(function (Builder $nested) use ($search): void {
                    $nested->where('booking_number', 'like', "%{$search}%")
                        ->orWhere('guest_name', 'like', "%{$search}%")
                        ->orWhere('guest_phone', 'like', "%{$search}%")
                        ->orWhereHas('property', fn (Builder $property) => $property->where('name', 'like', "%{$search}%"));
                }));

            $active = (clone $query)->where('status', '!=', Booking::STATUS_CANCELLED);
            $summary = [
                'bookings' => (clone $query)->count(),
                'active' => (clone $active)->count(),
                'cancelled' => (clone $query)->where('status', Booking::STATUS_CANCELLED)->count(),
                'room_nights' => (int) (clone $active)->sum('nights'),
                'revenue_minor' => (int) (clone $active)
                    ->selectRaw('COALESCE(SUM(total_amount_minor - discount_amount_minor + tax_amount_minor), 0) AS total')
                    ->value('total'),
            ];

            $bookings = $query
                ->latest('bookings.created_at')
                ->latest('bookings.id')
                ->paginate(25)
                ->withQueryString();
        }

        return view('admin.reports.index', [
            'tab' => $tab,
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'search' => $search,
            'propertyLabel' => $propertyLabel,
            'bookings' => $bookings,
            'payments' => $payments,
            'summary' => $summary,
            'bookingStatuses' => Booking::statusLabels(),
            'paymentStatuses' => [
                Payment::STATUS_CREATED => 'Initiated',
                Payment::STATUS_CAPTURED => 'Captured',
                Payment::STATUS_FAILED => 'Failed',
                Payment::STATUS_REFUNDED => 'Refunded',
            ],
            'navItems' => AdminNavigation::make('reports'),
        ]);
    }
}
