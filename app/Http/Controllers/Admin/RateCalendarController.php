<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyRate;
use App\Models\RatePlan;
use App\Models\RoomTypeInventory;
use App\Services\Booking\InventoryService;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RateCalendarController extends Controller
{
    private const WINDOW_DAYS = 14;

    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $properties = $scope->properties();
        $propertyId = $request->integer('property_id') ?: $scope->selectedPropertyId() ?: $properties->first()?->id;

        if ($propertyId) {
            abort_unless($scope->canAccessProperty($propertyId), 404);
        }

        $property = $propertyId ? $properties->firstWhere('id', $propertyId) : null;

        $start = $request->date('start')
            ? CarbonImmutable::parse($request->date('start'))
            : CarbonImmutable::today();
        $dates = collect(range(0, self::WINDOW_DAYS - 1))->map(fn (int $offset) => $start->addDays($offset));
        $end = $start->addDays(self::WINDOW_DAYS);

        $ratePlans = $property
            ? RatePlan::query()
                ->where('property_id', $property->id)
                ->with([
                    'roomType',
                    'dailyRates' => fn ($query) => $query
                        ->whereDate('date', '>=', $start->toDateString())
                        ->whereDate('date', '<', $end->toDateString()),
                ])
                ->orderBy('room_type_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
            : collect();

        $inventory = $property
            ? RoomTypeInventory::query()
                ->where('property_id', $property->id)
                ->whereDate('date', '>=', $start->toDateString())
                ->whereDate('date', '<', $end->toDateString())
                ->get()
                ->keyBy(fn (RoomTypeInventory $row) => $row->room_type_id.'|'.$row->date->toDateString())
            : collect();

        return view('admin.rate-calendar.index', [
            'property' => $property,
            'properties' => $properties,
            'ratePlans' => $ratePlans,
            'planGroups' => $ratePlans->groupBy(fn (RatePlan $plan) => $plan->roomType?->name ?? 'Unassigned Type'),
            'inventory' => $inventory,
            'dates' => $dates,
            'start' => $start,
            'navItems' => AdminNavigation::make('bookings'),
        ]);
    }

    public function update(Request $request, AdminPropertyScope $scope, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer'],
            'start' => ['required', 'date'],
            'rates' => ['nullable', 'array'],
            'rates.*.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rates.*.*.closed' => ['nullable', 'boolean'],
            'stop_sell' => ['nullable', 'array'],
            'stop_sell.*.*' => ['nullable', 'boolean'],
        ]);

        $propertyId = (int) $validated['property_id'];
        abort_unless($scope->canAccessProperty($propertyId), 404);

        $start = CarbonImmutable::parse($validated['start']);
        $end = $start->addDays(self::WINDOW_DAYS);

        $planIds = RatePlan::query()
            ->where('property_id', $propertyId)
            ->pluck('id')
            ->flip();

        $roomTypeIds = RatePlan::query()
            ->where('property_id', $propertyId)
            ->pluck('room_type_id')
            ->unique();

        DB::transaction(function () use ($validated, $planIds, $roomTypeIds, $propertyId, $start, $end, $inventoryService): void {
            foreach ($validated['rates'] ?? [] as $planId => $days) {
                if (! $planIds->has((int) $planId)) {
                    continue;
                }

                foreach ($days as $date => $cell) {
                    $day = CarbonImmutable::parse($date);

                    if ($day->lessThan($start) || $day->greaterThanOrEqualTo($end)) {
                        continue;
                    }

                    $price = $cell['price'] ?? null;
                    $closed = (bool) ($cell['closed'] ?? false);

                    if ($price === null || $price === '') {
                        continue;
                    }

                    $rate = DailyRate::query()
                        ->where('rate_plan_id', (int) $planId)
                        ->whereDate('date', $day->toDateString())
                        ->first() ?? new DailyRate([
                            'rate_plan_id' => (int) $planId,
                            'date' => $day->toDateString(),
                        ]);

                    $rate->price_minor = (int) round(((float) $price) * 100);
                    $rate->closed = $closed;
                    $rate->save();
                }
            }

            // Ensure inventory rows exist for the window before flagging stop-sell.
            foreach ($roomTypeIds as $roomTypeId) {
                $inventoryService->refreshNights($propertyId, $roomTypeId, $start, $end);

                $flags = $validated['stop_sell'][$roomTypeId] ?? [];

                RoomTypeInventory::query()
                    ->where('property_id', $propertyId)
                    ->where('room_type_id', $roomTypeId)
                    ->whereDate('date', '>=', $start->toDateString())
                    ->whereDate('date', '<', $end->toDateString())
                    ->get()
                    ->each(function (RoomTypeInventory $row) use ($flags): void {
                        $row->update(['stop_sell' => (bool) ($flags[$row->date->toDateString()] ?? false)]);
                    });
            }
        });

        return redirect()
            ->route('admin.rate-calendar.index', ['property_id' => $propertyId, 'start' => $start->toDateString()])
            ->with('status', 'Rates and inventory updated.');
    }
}
