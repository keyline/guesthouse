<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Amenity extends Model
{
    /** A shared property facility (Wi-Fi, Parking, Pool) — attached per property. */
    public const SCOPE_PROPERTY = 'property';

    /** An in-room feature (TV, AC, Bathtub) — promised per room type, overridable per room. */
    public const SCOPE_ROOM_CATEGORY = 'room_category';

    /**
     * @return array<string, string>
     */
    public static function scopeLabels(): array
    {
        return [
            self::SCOPE_PROPERTY => 'Added with property',
            self::SCOPE_ROOM_CATEGORY => 'Added with room type',
        ];
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'icon',
        'category',
        'type',
        'scope',
        'supports_fee',
        'is_guest_visible',
        'is_active',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'supports_fee' => 'boolean',
        'is_guest_visible' => 'boolean',
    ];

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class)->withTimestamps();
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'amenity_room_type')->withTimestamps();
    }

    public function propertyRoomTypes(): BelongsToMany
    {
        return $this->belongsToMany(PropertyRoomType::class, 'property_room_type_amenities')->withPivot(['availability_mode','is_free','fee_minor','charge_basis','details'])->withTimestamps();
    }

    public function roomOverrides(): HasMany
    {
        return $this->hasMany(RoomAmenityOverride::class);
    }

    public function banquets(): BelongsToMany
    {
        return $this->belongsToMany(Banquet::class, 'banquet_amenity')->withTimestamps();
    }
}
