<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A company's negotiated flat nightly price for one room type. */
class CorporateRoomRate extends Model
{
    protected $fillable = ['corporate_id', 'room_type_id', 'price_minor'];

    protected function casts(): array
    {
        return ['price_minor' => 'integer'];
    }

    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
