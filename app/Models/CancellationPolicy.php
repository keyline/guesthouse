<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A cancellation policy template: up to a few ordered tiers of
 * "until N hours before check-in → refund P%". Templates are resolved into an
 * absolute-deadline snapshot on each booking at creation time — after that the
 * booking's terms never change, whatever happens to the template.
 */
class CancellationPolicy extends Model
{
    use LogsActivity;

    public const CODE_FLEXIBLE = 'flexible';

    public const CODE_FREE_24H = 'free_24h';

    public const CODE_NON_REFUNDABLE = 'non_refundable';

    /** Keep templates readable: at most this many tiers. */
    public const MAX_TIERS = 3;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'tiers',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tiers' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class);
    }

    public static function byCode(string $code): ?self
    {
        return static::query()->where('code', $code)->first();
    }

    /**
     * Tiers sorted most-generous first (largest hours_before).
     *
     * @return list<array{hours_before: int, refund_percent: int}>
     */
    public function sortedTiers(): array
    {
        return collect($this->tiers ?? [])
            ->map(fn (array $tier) => [
                'hours_before' => (int) $tier['hours_before'],
                'refund_percent' => (int) $tier['refund_percent'],
            ])
            ->sortByDesc('hours_before')
            ->values()
            ->all();
    }

    /**
     * Resolve this template against a concrete check-in moment into the
     * immutable snapshot stored on the booking.
     *
     * @return array{policy_id: int, name: string, code: string, check_in_at: string,
     *     tiers: list<array{until: string, refund_percent: int}>}
     */
    public function snapshotFor(CarbonImmutable $checkInAt): array
    {
        return [
            'policy_id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'check_in_at' => $checkInAt->toIso8601String(),
            'tiers' => collect($this->sortedTiers())
                ->map(fn (array $tier) => [
                    'until' => $checkInAt->subHours($tier['hours_before'])->toIso8601String(),
                    'refund_percent' => $tier['refund_percent'],
                ])
                ->all(),
        ];
    }

    /**
     * Plain-language tier lines relative to check-in, for template screens.
     *
     * @return list<string>
     */
    public function describeLines(): array
    {
        $lines = [];

        foreach ($this->sortedTiers() as $tier) {
            $lines[] = sprintf(
                'Until %d %s before check-in — %d%% refund',
                $tier['hours_before'],
                str('hour')->plural($tier['hours_before']),
                $tier['refund_percent'],
            );
        }

        $lines[] = $lines === [] ? 'No refund on cancellation' : 'After that — no refund';

        return $lines;
    }

    /** Short label for search results and plan rows. */
    public function shortLabel(): string
    {
        $best = $this->sortedTiers()[0] ?? null;

        if ($best === null || $best['refund_percent'] < 1) {
            return 'Non-refundable';
        }

        return sprintf(
            '%s till %dh before check-in',
            $best['refund_percent'] >= 100 ? 'Free cancellation' : $best['refund_percent'].'% refund',
            $best['hours_before'],
        );
    }
}
