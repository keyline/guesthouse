<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Stay extends Model
{
    use LogsActivity;

    protected $fillable = ['booking_id', 'status', 'actual_check_in_at', 'actual_check_out_at', 'checked_in_by', 'checked_out_by', 'registration_accepted', 'check_in_notes', 'check_out_notes'];

    protected function casts(): array
    {
        return ['actual_check_in_at' => 'datetime', 'actual_check_out_at' => 'datetime', 'registration_accepted' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Stay $stay) => $stay->public_id ??= (string) Str::uuid());
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function events(): HasMany { return $this->hasMany(StayEvent::class); }
}
