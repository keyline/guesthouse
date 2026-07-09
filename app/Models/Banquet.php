<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banquet extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'name',
        'slug',
        'description',
        'capacity_min',
        'capacity_max',
        'base_price_minor',
        'currency',
        'setup_types',
        'amenities',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capacity_min' => 'integer',
            'capacity_max' => 'integer',
            'base_price_minor' => 'integer',
            'setup_types' => 'json',
            'amenities' => 'json',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Banquet $banquet): void {
            if (! $banquet->slug) {
                $banquet->slug = static::uniqueSlug($banquet->name, $banquet->property_id);
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(BanquetImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(BanquetPrice::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BanquetBooking::class);
    }

    public function amenitiesList(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'banquet_amenity');
    }

    public static function uniqueSlug(string $name, int $propertyId, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('property_id', $propertyId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
