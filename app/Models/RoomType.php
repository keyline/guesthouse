<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RoomType extends Model
{
    use LogsActivity;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'status',
        'max_adults',
        'max_children',
        'is_pet_friendly',
        'extra_bed_available',
        'max_extra_beds',
        'extra_bed_charge_minor',
        'extra_bed_charge_basis',
        'sort_order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'is_pet_friendly' => 'boolean',
            'extra_bed_available' => 'boolean',
            'max_extra_beds' => 'integer',
            'extra_bed_charge_minor' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RoomType $roomType): void {
            if (! $roomType->code) $roomType->code = static::uniqueCode($roomType->name);
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function propertyConfigurations(): HasMany
    {
        return $this->hasMany(PropertyRoomType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class);
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

    public static function makeCode(string $name): string
    {
        $code = Str::lower(Str::slug($name, '-'));

        return Str::limit($code ?: 'room-type', 40, '');
    }

    public static function uniqueCode(string $name, ?int $ignoreId = null): string
    {
        $base = static::makeCode($name);
        $candidate = $base;
        $number = 2;

        while (static::query()->where('code', $candidate)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $suffix = '-'.$number++;
            $candidate = Str::limit($base, 40 - strlen($suffix), '').$suffix;
        }

        return $candidate;
    }
}
