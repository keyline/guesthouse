<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Carbon\CarbonInterface;

class Property extends Model
{
    use LogsActivity;

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

    public function roomTypeConfigurations(): HasMany
    {
        return $this->hasMany(PropertyRoomType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest('check_in_date');
    }

    public function ruleSets(): HasMany
    {
        return $this->hasMany(PropertyRuleSet::class);
    }

    public function publishedRuleSet(): HasOne
    {
        return $this->hasOne(PropertyRuleSet::class)
            ->where('status', PropertyRuleSet::STATUS_PUBLISHED)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()))
            ->latestOfMany('version');
    }

    public function publishedRulesFor(CarbonInterface|string|null $date = null): ?PropertyRuleSet
    {
        $date = $date ? \Carbon\CarbonImmutable::parse($date)->toDateString() : today()->toDateString();

        return $this->ruleSets()
            ->where('status', PropertyRuleSet::STATUS_PUBLISHED)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->with('rules')->latest('version')->first();
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
