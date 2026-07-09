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
        'location',
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
        'show_on_home',
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
            'show_on_home' => 'boolean',
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

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class)->orderBy('room_number');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest('check_in_date');
    }

    public function banquets(): HasMany
    {
        return $this->hasMany(Banquet::class)->orderBy('sort_order')->orderBy('name');
    }

    public function primaryImage(): HasMany
    {
        return $this->images()->where('is_primary', true);
    }

    public function formattedBasePrice(): string
    {
        return $this->currency.' '.number_format($this->base_price_minor / 100, 2);
    }

    public function locationDropdownLabel(): string
    {
        return collect([$this->name, $this->location, $this->city])
            ->filter(fn (?string $part) => filled($part))
            ->unique()
            ->join(' - ');
    }

    public function getRouteKey(): string
    {
        $encrypted = openssl_encrypt(
            (string) $this->getKey(),
            'AES-256-CBC',
            $this->routeKeySecret(),
            OPENSSL_RAW_DATA,
            $this->routeKeyIv(),
        );

        return rtrim(strtr(base64_encode($encrypted ?: ''), '+/', '-_'), '=');
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $encrypted = base64_decode(strtr((string) $value, '-_', '+/'), true);

        if ($encrypted === false) {
            return null;
        }

        $id = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $this->routeKeySecret(),
            OPENSSL_RAW_DATA,
            $this->routeKeyIv(),
        );

        if (! is_string($id) || ! ctype_digit($id)) {
            return null;
        }

        return $this->whereKey($id)->first();
    }

    private function routeKeySecret(): string
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            if ($decoded !== false) {
                return hash('sha256', $decoded, true);
            }
        }

        return hash('sha256', $key, true);
    }

    private function routeKeyIv(): string
    {
        return substr(hash_hmac('sha256', 'property-route-key', $this->routeKeySecret(), true), 0, 16);
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
