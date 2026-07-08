<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Property extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const TYPE_GUEST_HOUSE = 'guest_house';

    public const TYPE_BANQUET = 'banquet';

    public const TYPE_MIXED = 'mixed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'property_type',
        'status',
        'city',
        'state',
        'country',
        'postal_code',
        'address',
        'phone',
        'email',
        'manager_name',
        'check_in_time_minutes',
        'check_out_time_minutes',
        'base_price_minor',
        'currency',
        'sort_order',
        'short_description',
        'description',
        'policies',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'base_price_minor' => 'integer',
            'check_in_time_minutes' => 'integer',
            'check_out_time_minutes' => 'integer',
            'policies' => 'array',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Property $property): void {
            if (! $property->slug) {
                $property->slug = static::uniqueSlug($property->name);
            }
        });
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class)->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class)->orderBy('sort_order')->orderBy('name');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class)->orderBy('room_number');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest('check_in_date');
    }

    public function primaryImage(): HasMany
    {
        return $this->images()->where('is_primary', true);
    }

    public function formattedBasePrice(): string
    {
        return $this->currency.' '.number_format($this->base_price_minor / 100, 2);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
