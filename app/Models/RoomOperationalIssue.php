<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomOperationalIssue extends Model
{
    protected $fillable = ['room_id','amenity_id','issue_type','description','severity','blocks_sale','blocks_assignment','starts_at','expected_resolution_at','resolved_at'];
    protected function casts(): array { return ['blocks_sale'=>'boolean','blocks_assignment'=>'boolean','starts_at'=>'datetime','expected_resolution_at'=>'datetime','resolved_at'=>'datetime']; }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function amenity(): BelongsTo { return $this->belongsTo(Amenity::class); }
    public function scopeOpen(Builder $query): Builder { return $query->whereNull('resolved_at'); }
}
