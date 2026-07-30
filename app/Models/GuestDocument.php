<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GuestDocument extends Model
{
    use LogsActivity;

    protected $fillable = ['booking_guest_id', 'document_type', 'document_number_encrypted', 'document_number_masked', 'issuing_country', 'expires_at', 'front_path', 'back_path', 'verification_status', 'verified_by', 'verified_at', 'retention_until'];
    protected $auditExclude = ['document_number_encrypted', 'front_path', 'back_path'];

    protected function casts(): array
    {
        return ['document_number_encrypted' => 'encrypted', 'expires_at' => 'date', 'verified_at' => 'datetime', 'retention_until' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (GuestDocument $document) => $document->public_id ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string { return 'public_id'; }
    public function guest(): BelongsTo { return $this->belongsTo(BookingGuest::class, 'booking_guest_id'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    /** Audit rows must stay visible to property-scoped admins. */
    public function auditPropertyId(): ?int
    {
        return $this->guest?->booking?->property_id;
    }

    /** e.g. "Voter id ••••7890 — Priya Sharma (BK260711YEYGAD)". */
    public function auditLabel(): string
    {
        return trim(sprintf(
            '%s %s — %s (%s)',
            ucfirst(str_replace('_', ' ', (string) $this->document_type)),
            $this->document_number_masked ?: 'no number',
            $this->guest?->full_name ?? 'guest',
            $this->guest?->booking?->booking_number ?? '—',
        ));
    }
}
