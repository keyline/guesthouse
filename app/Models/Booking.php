<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Booking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_CHECKED_OUT = 'checked_out';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_DIRECT = 'direct';

    public const SOURCE_PHONE = 'phone';

    public const SOURCE_WALK_IN = 'walk_in';

    public const SOURCE_ONLINE = 'online';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'booking_number',
        'property_id',
        'room_type_id',
        'room_id',
        'user_id',
        'status',
        'source',
        'guest_name',
        'guest_email',
        'guest_phone',
        'check_in_date',
        'check_out_date',
        'nights',
        'adults',
        'children',
        'total_amount_minor',
        'currency',
        'special_requests',
        'internal_notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'cancelled_at' => 'datetime',
            'nights' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'total_amount_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if (! $booking->booking_number) {
                $booking->booking_number = static::nextBookingNumber();
            }
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

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedTotal(): string
    {
        return $this->currency.' '.number_format($this->total_amount_minor / 100, 2);
    }

    /**
     * @return list<string>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CHECKED_IN,
        ];
    }

    public static function nextBookingNumber(): string
    {
        return 'BK'.now()->format('ymd').Str::upper(Str::random(6));
    }
}
