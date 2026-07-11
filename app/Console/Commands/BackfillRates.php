<?php

namespace App\Console\Commands;

use App\Models\DailyRate;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Services\Booking\InventoryService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class BackfillRates extends Command
{
    protected $signature = 'rates:backfill {--days=180 : Nights ahead to seed}';

    protected $description = 'Seed default rate plans, daily rates, and room-type inventory for every property/room-type pair that has rooms. Idempotent: existing rows are never overwritten.';

    public function handle(InventoryService $inventory): int
    {
        $days = max(1, (int) $this->option('days'));
        $start = CarbonImmutable::today();
        $end = $start->addDays($days);

        $pairs = Room::query()
            ->selectRaw('property_id, room_type_id')
            ->groupBy('property_id', 'room_type_id')
            ->get();

        if ($pairs->isEmpty()) {
            $this->warn('No rooms found — nothing to backfill.');

            return self::SUCCESS;
        }

        $properties = Property::query()
            ->whereIn('id', $pairs->pluck('property_id')->unique())
            ->get()
            ->keyBy('id');

        $plansCreated = 0;
        $ratesCreated = 0;

        foreach ($pairs as $pair) {
            $property = $properties[$pair->property_id] ?? null;

            if (! $property) {
                continue;
            }

            $plan = RatePlan::query()->firstOrCreate(
                [
                    'property_id' => $pair->property_id,
                    'room_type_id' => $pair->room_type_id,
                    'code' => 'STD-EP',
                ],
                [
                    'name' => 'Standard Rate (EP)',
                    'meal_plan' => RatePlan::MEAL_PLAN_EP,
                    'is_refundable' => true,
                    'default_price_minor' => $property->base_price_minor,
                    'currency' => $property->currency ?: 'INR',
                    'status' => RatePlan::STATUS_ACTIVE,
                ],
            );

            if ($plan->wasRecentlyCreated) {
                $plansCreated++;
            }

            $existingDates = DailyRate::query()
                ->where('rate_plan_id', $plan->id)
                ->whereDate('date', '>=', $start->toDateString())
                ->whereDate('date', '<', $end->toDateString())
                ->pluck('date')
                ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
                ->flip();

            $rows = [];

            for ($night = $start; $night->lessThan($end); $night = $night->addDay()) {
                if ($existingDates->has($night->toDateString())) {
                    continue;
                }

                $rows[] = [
                    'rate_plan_id' => $plan->id,
                    'date' => $night->toDateString(),
                    'price_minor' => $plan->default_price_minor,
                    'min_stay' => 1,
                    'closed' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($rows !== []) {
                DailyRate::query()->insert($rows);
                $ratesCreated += count($rows);
            }

            $inventory->refreshNights($pair->property_id, $pair->room_type_id, $start, $end);
        }

        $this->info("Backfill complete: {$pairs->count()} property/room-type pair(s), {$plansCreated} rate plan(s) created, {$ratesCreated} daily rate(s) seeded, inventory refreshed for {$days} night(s).");

        return self::SUCCESS;
    }
}
