<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StayEvent extends Model
{
    public $timestamps = false;
    protected $fillable = ['stay_id', 'actor_id', 'event_type', 'payload', 'created_at'];
    protected function casts(): array { return ['payload' => 'array', 'created_at' => 'datetime']; }
    public function stay(): BelongsTo { return $this->belongsTo(Stay::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
