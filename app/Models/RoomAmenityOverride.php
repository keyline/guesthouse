<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class RoomAmenityOverride extends Model
{
    public const PRESENT = 'present';
    public const MISSING = 'missing';
    protected $fillable = ['room_id','amenity_id','state','reason','effective_from','effective_until'];
    protected function casts(): array { return ['effective_from'=>'datetime','effective_until'=>'datetime']; }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function amenity(): BelongsTo { return $this->belongsTo(Amenity::class); }
    public function scopeCurrentlyEffective(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', now()));
    }
}
