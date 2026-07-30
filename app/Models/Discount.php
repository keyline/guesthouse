<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A discount is either a coupon (has a code the guest types in) or an
 * automatic offer (no code — applies by itself whenever it matches).
 */
class Discount extends Model
{
    use LogsActivity;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'room_type_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_minor',
        'valid_from',
        'valid_until',
        'min_nights',
        'min_amount_minor',
        'max_uses',
        'times_used',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'discount_value' => 'integer',
            'max_discount_minor' => 'integer',
            'min_nights' => 'integer',
            'min_amount_minor' => 'integer',
            'max_uses' => 'integer',
            'times_used' => 'integer',
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

    public function isCoupon(): bool
    {
        return $this->code !== null;
    }

    public function hasUsesLeft(): bool
    {
        return $this->max_uses === null || $this->times_used < $this->max_uses;
    }

    /** The guest-facing line on invoices and confirmations. */
    public function label(): string
    {
        return $this->code ?: $this->name;
    }

    /** Discount amount in minor units for a given pre-tax tariff, never above it. */
    public function discountFor(int $tariffMinor): int
    {
        if ($tariffMinor < 1) {
            return 0;
        }

        $amount = $this->discount_type === self::TYPE_PERCENT
            ? (int) round($tariffMinor * $this->discount_value / 10000)
            : $this->discount_value;

        if ($this->max_discount_minor !== null) {
            $amount = min($amount, $this->max_discount_minor);
        }

        return min($amount, $tariffMinor);
    }

    /**
     * Whether this discount can be applied to the given stay at all. Pass the
     * room type when quoting a concrete plan; null skips the category check
     * (structural checks against a whole cart).
     */
    public function appliesTo(int $propertyId, CarbonInterface $checkIn, CarbonInterface $checkOut, int $tariffMinor, ?int $roomTypeId = null): bool
    {
        $nights = max(1, $checkIn->diffInDays($checkOut));

        return $this->status === self::STATUS_ACTIVE
            && ($this->property_id === null || $this->property_id === $propertyId)
            && ($this->room_type_id === null || $roomTypeId === null || $this->room_type_id === $roomTypeId)
            && ($this->valid_from === null || ! $checkIn->lessThan($this->valid_from))
            && ($this->valid_until === null || ! $checkIn->greaterThan($this->valid_until))
            && ($this->min_nights === null || $nights >= $this->min_nights)
            && ($this->min_amount_minor === null || $tariffMinor >= $this->min_amount_minor)
            && $this->hasUsesLeft();
    }

    /**
     * Give back the uses consumed by a cancelled booking. A multi-room group
     * counts as one use, so the counter only drops once the last room of the
     * group referencing the discount is cancelled.
     */
    public static function releaseFor(Booking $booking): void
    {
        $group = $booking->booking_group_code
            ? Booking::query()->where('booking_group_code', $booking->booking_group_code)->get()
            : collect([$booking->fresh() ?? $booking]);

        foreach ($group->pluck('discount_id')->filter()->unique() as $discountId) {
            $stillInUse = $group->first(
                fn (Booking $room) => $room->discount_id === $discountId && $room->status !== Booking::STATUS_CANCELLED
            );

            if (! $stillInUse) {
                static::query()->whereKey($discountId)->where('times_used', '>', 0)->decrement('times_used');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_PERCENT => 'Percentage off',
            self::TYPE_FIXED => 'Fixed amount off',
        ];
    }
}
