<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomTypeInventory extends Model
{
    use LogsActivity;

    /**
     * Rows are created and rooms_sold is recomputed by the system on every
     * booking sync — only manual edits (stop_sell, total_rooms) are audited.
     *
     * @var list<string>
     */
    protected $auditEvents = ['updated'];

    /**
     * @var list<string>
     */
    protected $auditExclude = ['rooms_sold'];

    protected $table = 'room_type_inventory';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'room_type_id',
        'date',
        'total_rooms',
        'rooms_sold',
        'stop_sell',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_rooms' => 'integer',
            'rooms_sold' => 'integer',
            'stop_sell' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function available(): int
    {
        if ($this->stop_sell) {
            return 0;
        }

        return max(0, $this->total_rooms - $this->rooms_sold);
    }
}
