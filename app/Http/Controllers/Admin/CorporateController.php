<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CorporateRequest;
use App\Models\Booking;
use App\Models\Corporate;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Support\AdminNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CorporateController extends Controller
{
    public function index(): View
    {
        $corporates = Corporate::query()
            ->withCount(['bookings', 'guests'])
            ->orderByDesc('is_active')
            ->orderBy('legal_name')
            ->paginate(20)
            ->withQueryString();

        $unbilled = Booking::query()
            ->whereIn('corporate_id', $corporates->pluck('id'))
            ->where('billing', Booking::BILLING_CORPORATE)
            ->where('payment_status', Booking::PAYMENT_UNPAID)
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->selectRaw('corporate_id, count(*) as stays, sum(total_amount_minor - discount_amount_minor + tax_amount_minor) as due_minor')
            ->groupBy('corporate_id')
            ->get()
            ->keyBy('corporate_id');

        return view('admin.corporates.index', [
            'corporates' => $corporates,
            'unbilled' => $unbilled,
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function create(): View
    {
        return view('admin.corporates.create', [
            'corporate' => new Corporate(['country' => 'India', 'is_active' => true]),
            'roomTypes' => $this->roomTypeRows(),
            'rateCard' => [],
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function store(CorporateRequest $request): RedirectResponse
    {
        $corporate = DB::transaction(function () use ($request): Corporate {
            $corporate = Corporate::query()->create(
                $request->attributesForModel() + ['is_active' => true]
            );
            $this->syncRateCard($corporate, $request->rateCard());

            return $corporate;
        });

        return redirect()
            ->route('admin.corporates.show', $corporate)
            ->with('status', 'Company "'.$corporate->displayName().'" created.');
    }

    public function show(Corporate $corporate): View
    {
        $corporate->load(['roomRates.roomType']);

        $bookings = $corporate->bookings()
            ->with(['property', 'roomType'])
            ->orderByDesc('check_in_date')
            ->paginate(15);

        $unbilled = $corporate->bookings()
            ->where('billing', Booking::BILLING_CORPORATE)
            ->where('payment_status', Booking::PAYMENT_UNPAID)
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->get();

        return view('admin.corporates.show', [
            'corporate' => $corporate,
            'bookings' => $bookings,
            'unbilledCount' => $unbilled->count(),
            'unbilledMinor' => $unbilled->sum(fn (Booking $booking) => $booking->grossTotalMinor()),
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function edit(Corporate $corporate): View
    {
        $corporate->load('roomRates');

        return view('admin.corporates.edit', [
            'corporate' => $corporate,
            'roomTypes' => $this->roomTypeRows(),
            'rateCard' => $corporate->roomRates->pluck('price_minor', 'room_type_id')->all(),
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function update(CorporateRequest $request, Corporate $corporate): RedirectResponse
    {
        DB::transaction(function () use ($request, $corporate): void {
            $corporate->update($request->attributesForModel());
            $this->syncRateCard($corporate, $request->rateCard());
        });

        return redirect()
            ->route('admin.corporates.show', $corporate)
            ->with('status', 'Company "'.$corporate->displayName().'" updated.');
    }

    public function toggle(Corporate $corporate): RedirectResponse
    {
        $corporate->update(['is_active' => ! $corporate->is_active]);

        return back()->with('status', '"'.$corporate->displayName().'" is now '.($corporate->is_active ? 'active' : 'inactive').'.');
    }

    public function destroy(Corporate $corporate): RedirectResponse
    {
        if ($corporate->bookings()->exists() || $corporate->guests()->exists()) {
            return back()->withErrors([
                'corporate' => 'This company has bookings or linked guests — deactivate it instead of deleting.',
            ]);
        }

        $corporate->delete();

        return redirect()
            ->route('admin.corporates.index')
            ->with('status', 'Company deleted.');
    }

    /** Blank price removes the negotiated rate; filled price creates/updates it. */
    private function syncRateCard(Corporate $corporate, array $rates): void
    {
        $corporate->roomRates()->whereNotIn('room_type_id', array_keys($rates))->delete();

        foreach ($rates as $roomTypeId => $priceMinor) {
            $corporate->roomRates()->updateOrCreate(
                ['room_type_id' => $roomTypeId],
                ['price_minor' => $priceMinor],
            );
        }
    }

    /**
     * Active room types with a reference price (cheapest active plan's rack
     * rate) so the owner can see what they are negotiating against.
     *
     * @return \Illuminate\Support\Collection<int, array{roomType: RoomType, reference_minor: ?int}>
     */
    private function roomTypeRows(): \Illuminate\Support\Collection
    {
        $referencePrices = RatePlan::query()
            ->where('status', RatePlan::STATUS_ACTIVE)
            ->orderBy('default_price_minor')
            ->get()
            ->groupBy('room_type_id')
            ->map(fn ($plans) => $plans->first()->default_price_minor);

        return RoomType::query()
            ->where('status', RoomType::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (RoomType $roomType) => [
                'roomType' => $roomType,
                'reference_minor' => $referencePrices[$roomType->id] ?? null,
            ]);
    }
}
