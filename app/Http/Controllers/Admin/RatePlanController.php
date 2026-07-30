<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyRate;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RatePlanController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $properties = $scope->properties();
        // The shared top-bar context is the single source of truth. Keeping a
        // second property selector on this page allowed the two controls to
        // disagree and made it too easy to edit the wrong property's rates.
        $propertyId = $scope->selectedPropertyId();

        if ($propertyId) {
            abort_unless($scope->canAccessProperty($propertyId), 404);
        }

        $property = $propertyId ? $properties->firstWhere('id', $propertyId) : null;

        $roomCounts = $property
            ? Room::query()
                ->where('property_id', $property->id)
                ->selectRaw('room_type_id, count(*) as total')
                ->selectRaw("sum(case when is_online_bookable and status = 'available' then 1 else 0 end) as online")
                ->groupBy('room_type_id')
                ->get()
                ->keyBy('room_type_id')
            : collect();

        $plans = $property
            ? RatePlan::query()
                ->where('property_id', $property->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('room_type_id')
            : collect();

        $roomTypes = RoomType::query()
            ->whereIn('id', $roomCounts->keys()->merge($plans->keys())->unique())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.rate-plans.index', [
            'property' => $property,
            'roomTypes' => $roomTypes,
            'roomCounts' => $roomCounts,
            'plans' => $plans,
            'mealPlans' => RatePlan::mealPlans(),
            'cancellationPolicies' => \App\Models\CancellationPolicy::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function store(Request $request, AdminPropertyScope $scope): RedirectResponse
    {
        $validated = $request->validate([
            'room_type_id' => ['required', 'integer', Rule::exists(RoomType::class, 'id')],
            'meal_plan' => ['required', Rule::in(array_keys(RatePlan::mealPlans()))],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $propertyId = $scope->selectedPropertyId();

        if (! $propertyId) {
            return back()->withErrors([
                'property_id' => 'Select one property from the top banner before adding a rate plan.',
            ]);
        }

        abort_unless($scope->canAccessProperty($propertyId), 404);

        $roomTypeId = (int) $validated['room_type_id'];
        $mealPlan = $validated['meal_plan'];
        $amountMinor = (int) round(((float) $validated['amount']) * 100);

        $code = strtoupper($mealPlan);

        if (RatePlan::query()->where('property_id', $propertyId)->where('room_type_id', $roomTypeId)->where('code', $code)->exists()) {
            return back()->withErrors(['meal_plan' => 'A '.$code.' plan already exists for this room type.']);
        }

        $basePlan = RatePlan::query()
            ->where('property_id', $propertyId)
            ->where('room_type_id', $roomTypeId)
            ->where('meal_plan', RatePlan::MEAL_PLAN_EP)
            ->where('status', RatePlan::STATUS_ACTIVE)
            ->orderBy('id')
            ->first();

        // With an EP base plan, "amount" is the supplement (e.g. breakfast
        // +300); without one, it is the absolute nightly price.
        $isDerived = $basePlan !== null && $mealPlan !== RatePlan::MEAL_PLAN_EP;

        DB::transaction(function () use ($propertyId, $roomTypeId, $mealPlan, $code, $amountMinor, $basePlan, $isDerived): void {
            $plan = RatePlan::query()->create([
                'property_id' => $propertyId,
                'room_type_id' => $roomTypeId,
                'name' => $this->defaultName($mealPlan),
                'code' => $code,
                'meal_plan' => $mealPlan,
                'is_refundable' => true,
                'cancellation_policy_id' => \App\Models\CancellationPolicy::byCode(\App\Models\CancellationPolicy::CODE_FLEXIBLE)?->id,
                'default_price_minor' => $isDerived
                    ? $basePlan->default_price_minor + $amountMinor
                    : $amountMinor,
                'currency' => $basePlan?->currency ?? 'INR',
                'status' => RatePlan::STATUS_ACTIVE,
                'sort_order' => $isDerived ? 1 : 0,
            ]);

            if ($isDerived) {
                $rows = DailyRate::query()
                    ->where('rate_plan_id', $basePlan->id)
                    ->whereDate('date', '>=', now()->toDateString())
                    ->get()
                    ->map(fn (DailyRate $rate) => [
                        'rate_plan_id' => $plan->id,
                        'date' => $rate->date->toDateString(),
                        'price_minor' => $rate->price_minor + $amountMinor,
                        'min_stay' => $rate->min_stay,
                        'closed' => $rate->closed,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->all();

                if ($rows !== []) {
                    DailyRate::query()->insert($rows);
                }
            }
        });

        return redirect()
            ->route('admin.rate-plans.index')
            ->with('status', $this->defaultName($mealPlan).' plan created.');
    }

    public function update(Request $request, RatePlan $ratePlan, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($ratePlan->property_id), 404);

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'cancellation_policy_id' => ['nullable', 'integer', Rule::exists(\App\Models\CancellationPolicy::class, 'id')],
        ]);

        $newRack = (int) round(((float) $validated['price']) * 100);
        $oldRack = $ratePlan->default_price_minor;

        DB::transaction(function () use ($ratePlan, $newRack, $oldRack, $validated): void {
            $ratePlan->update([
                'default_price_minor' => $newRack,
                // Applies to new bookings only — existing bookings keep their
                // frozen policy snapshot.
                'cancellation_policy_id' => $validated['cancellation_policy_id'] ?? $ratePlan->cancellation_policy_id,
            ]);

            // Roll the new rack rate onto future nights still at the old rack
            // price; date-specific overrides are left untouched.
            DailyRate::query()
                ->where('rate_plan_id', $ratePlan->id)
                ->whereDate('date', '>=', now()->toDateString())
                ->where('price_minor', $oldRack)
                ->get()
                ->each(fn (DailyRate $rate) => $rate->update(['price_minor' => $newRack]));
        });

        return redirect()
            ->route('admin.rate-plans.index')
            ->with('status', $ratePlan->name.' price updated.');
    }

    public function toggle(RatePlan $ratePlan, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($ratePlan->property_id), 404);

        $ratePlan->update([
            'status' => $ratePlan->status === RatePlan::STATUS_ACTIVE
                ? RatePlan::STATUS_INACTIVE
                : RatePlan::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('admin.rate-plans.index')
            ->with('status', $ratePlan->name.' is now '.$ratePlan->status.'.');
    }

    private function defaultName(string $mealPlan): string
    {
        return match ($mealPlan) {
            RatePlan::MEAL_PLAN_CP => 'With Breakfast (CP)',
            RatePlan::MEAL_PLAN_MAP => 'Breakfast + One Meal (MAP)',
            RatePlan::MEAL_PLAN_AP => 'All Meals (AP)',
            default => 'Standard Rate (EP)',
        };
    }
}
