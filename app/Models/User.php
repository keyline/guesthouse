<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use LogsActivity;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_PROPERTY_MANAGER = 'property_manager';

    public const ROLE_CUSTOMER = 'customer';

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->public_id ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'phone',
        'date_of_birth',
        'gender',
        'nationality',
        'id_document_type',
        'id_document_number',
        'address',
        'guest_notes',
        'last_booking_at',
        'customer_type',
        'corporate_id',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone_country_code',
        'phone_national',
        'phone_e164',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([self::ROLE_SUPER_ADMIN, self::ROLE_PROPERTY_MANAGER]);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest('check_in_date');
    }

    public function managedProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class)->withTimestamps();
    }

    public function corporate(): BelongsTo { return $this->belongsTo(Corporate::class); }

    public function formattedAddress(): string
    {
        return collect([$this->address_line_1 ?: $this->address, $this->address_line_2, $this->city, $this->state, $this->postal_code, $this->country])->filter()->join(', ');
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'last_booking_at' => 'datetime',
        ];
    }
}
