<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Corporate extends Model
{
    use LogsActivity;

    protected $fillable = ['legal_name', 'trade_name', 'gstin', 'pan', 'booking_code', 'discount_type', 'discount_value', 'default_billing', 'contact_name', 'email', 'phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean', 'discount_value' => 'integer']; }
    protected static function booted(): void { static::creating(fn (Corporate $corporate) => $corporate->public_id ??= (string) Str::uuid()); }
    public function getRouteKeyName(): string { return 'public_id'; }
    public function guests(): HasMany { return $this->hasMany(User::class); }
    public function formattedAddress(): string { return collect([$this->address_line_1, $this->address_line_2, $this->city, $this->state, $this->postal_code, $this->country])->filter()->join(', '); }

    protected function bookingCode(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => $value === null ? null : strtoupper(trim($value)));
    }

    public function roomRates(): HasMany
    {
        return $this->hasMany(CorporateRoomRate::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Name shown to guests and on booking lines. */
    public function displayName(): string
    {
        return $this->trade_name ?: $this->legal_name;
    }

    /** Whether employee stays are billed to the company by default. */
    public function billsToCompany(): bool
    {
        return $this->default_billing === Booking::BILLING_CORPORATE;
    }

    /** Negotiated nightly price for a room type, or null when not agreed. */
    public function nightlyRateFor(int $roomTypeId): ?int
    {
        return $this->roomRates->firstWhere('room_type_id', $roomTypeId)?->price_minor;
    }

    /**
     * Blanket discount off the normal tariff, for room types without a
     * negotiated nightly price. Same conventions as Discount::discountFor.
     */
    public function blanketDiscountFor(int $tariffMinor): int
    {
        if ($this->discount_type === null || ! $this->discount_value || $tariffMinor < 1) {
            return 0;
        }

        $amount = $this->discount_type === Discount::TYPE_PERCENT
            ? (int) round($tariffMinor * $this->discount_value / 10000)
            : $this->discount_value;

        return min($amount, $tariffMinor);
    }
}
