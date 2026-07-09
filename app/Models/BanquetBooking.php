<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanquetBooking extends Model
{
    public const STATUS_INQUIRY = 'inquiry';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'banquet_id',
        'property_id',
        'event_name',
        'event_type',
        'guest_email',
        'guest_phone',
        'guest_name',
        'expected_guests',
        'setup_type',
        'event_date',
        'event_start_time',
        'event_end_time',
        'special_requirements',
        'status',
        'notes',
        'total_price_minor',
    ];

    protected function casts(): array
    {
        return [
            'expected_guests' => 'integer',
            'event_date' => 'date',
            'total_price_minor' => 'integer',
        ];
    }

    public function banquet(): BelongsTo
    {
        return $this->belongsTo(Banquet::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
