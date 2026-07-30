<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RatePlan extends Model
{
    use LogsActivity;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const MEAL_PLAN_EP = 'ep';

    public const MEAL_PLAN_CP = 'cp';

    public const MEAL_PLAN_MAP = 'map';

    public const MEAL_PLAN_AP = 'ap';

    /**
     * The rate plan a room of this type is priced with when staff do not pick one.
     */
    public static function defaultFor(int $propertyId, int $roomTypeId): ?self
    {
        return static::query()
            ->where('property_id', $propertyId)
            ->where('room_type_id', $roomTypeId)
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'room_type_id',
        'name',
        'code',
        'meal_plan',
        'is_refundable',
        'cancellation_policy',
        'cancellation_policy_id',
        'default_price_minor',
        'currency',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_refundable' => 'boolean',
            'default_price_minor' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (RatePlan $plan): void {
            $type = $plan->roomType()->first();
            PropertyRoomType::query()->firstOrCreate(
                ['property_id' => $plan->property_id, 'room_type_id' => $plan->room_type_id],
                ['max_adults' => $type?->max_adults ?? 2, 'max_children' => $type?->max_children ?? 0,
                    'is_pet_friendly' => $type?->is_pet_friendly ?? false,
                    'extra_bed_available' => $type?->extra_bed_available ?? false,
                    'max_extra_beds' => $type?->max_extra_beds ?? 0,
                    'extra_bed_charge_minor' => $type?->extra_bed_charge_minor ?? 0,
                    'extra_bed_charge_basis' => $type?->extra_bed_charge_basis ?? 'per_night']
            );
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function cancellationPolicy(): BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    /**
     * The policy governing new bookings on this plan: the assigned template,
     * falling back to the legacy boolean for plans never migrated.
     */
    public function effectiveCancellationPolicy(): ?CancellationPolicy
    {
        return $this->cancellationPolicy
            ?? CancellationPolicy::byCode(
                $this->is_refundable ? CancellationPolicy::CODE_FLEXIBLE : CancellationPolicy::CODE_NON_REFUNDABLE
            );
    }

    public function dailyRates(): HasMany
    {
        return $this->hasMany(DailyRate::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return array<string, string>
     */
    public static function mealPlans(): array
    {
        return [
            self::MEAL_PLAN_EP => 'EP (Room only)',
            self::MEAL_PLAN_CP => 'CP (With breakfast)',
            self::MEAL_PLAN_MAP => 'MAP (Breakfast + one meal)',
            self::MEAL_PLAN_AP => 'AP (All meals)',
        ];
    }
}
