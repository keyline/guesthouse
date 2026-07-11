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

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
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
