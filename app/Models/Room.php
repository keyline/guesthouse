<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_BLOCKED = 'blocked';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'room_type_id',
        'room_number',
        'floor',
        'status',
        'is_smoking',
        'is_accessible',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_smoking' => 'boolean',
            'is_accessible' => 'boolean',
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

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
