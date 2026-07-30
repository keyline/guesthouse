<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BookingGuest extends Model
{
    use LogsActivity;

    protected $fillable = ['booking_id', 'user_id', 'role', 'guest_type', 'full_name', 'phone', 'email', 'date_of_birth', 'nationality', 'address_line_1', 'city', 'state', 'postal_code', 'country', 'id_verification_status', 'is_staying', 'checked_in_at', 'checked_out_at'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'is_staying' => 'boolean', 'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BookingGuest $guest) => $guest->public_id ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string { return 'public_id'; }

    /** Audit rows must stay visible to property-scoped admins. */
    public function auditPropertyId(): ?int
    {
        return $this->booking?->property_id;
    }

    public function auditLabel(): string
    {
        return trim($this->full_name.' ('.($this->booking?->booking_number ?? '—').')');
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function documents(): HasMany { return $this->hasMany(GuestDocument::class); }
    public function isAdult(): bool { return $this->guest_type === 'adult'; }
    public function isForeignNational(): bool { return strcasecmp($this->nationality, 'Indian') !== 0; }
}
