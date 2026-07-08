<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RoomType extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'name',
        'code',
        'status',
        'max_adults',
        'max_children',
        'base_occupancy',
        'base_price_minor',
        'currency',
        'sort_order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'base_price_minor' => 'integer',
            'base_occupancy' => 'integer',
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RoomType $roomType): void {
            if (! $roomType->code) {
                $roomType->code = static::makeCode($roomType->name);
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomTypeImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_room_type')->withTimestamps();
    }

    public function primaryImage(): HasMany
    {
        return $this->images()->where('is_primary', true);
    }

    public function formattedBasePrice(): string
    {
        return $this->currency.' '.number_format($this->base_price_minor / 100, 2);
    }

    public static function makeCode(string $name): string
    {
        $code = Str::upper(Str::slug($name, '-'));

        return Str::limit($code ?: 'ROOM', 40, '');
    }
}
