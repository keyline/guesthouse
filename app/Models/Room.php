<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use LogsActivity;

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
        'is_online_bookable',
        'is_smoking',
        'is_accessible',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_online_bookable' => 'boolean',
            'is_smoking' => 'boolean',
            'is_accessible' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (Room $room) => PropertyRoomType::query()->firstOrCreate(
            ['property_id' => $room->property_id, 'room_type_id' => $room->room_type_id],
            self::configurationDefaults($room->roomType()->first())
        ));
    }

    private static function configurationDefaults(?RoomType $type): array
    {
        return [
            'max_adults' => $type?->max_adults ?? 2, 'max_children' => $type?->max_children ?? 0,
            'is_pet_friendly' => $type?->is_pet_friendly ?? false,
            'extra_bed_available' => $type?->extra_bed_available ?? false,
            'max_extra_beds' => $type?->max_extra_beds ?? 0,
            'extra_bed_charge_minor' => $type?->extra_bed_charge_minor ?? 0,
            'extra_bed_charge_basis' => $type?->extra_bed_charge_basis ?? 'per_night',
        ];
    }

    public function scopeOnlineBookable(Builder $query): Builder
    {
        return $query
            ->where('is_online_bookable', true)
            ->where('status', self::STATUS_AVAILABLE);
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

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class);
    }

    public function amenityOverrides(): HasMany
    {
        return $this->hasMany(RoomAmenityOverride::class);
    }

    public function operationalIssues(): HasMany
    {
        return $this->hasMany(RoomOperationalIssue::class);
    }
}
