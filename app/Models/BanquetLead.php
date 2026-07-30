<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanquetLead extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CLOSED = 'closed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'banquet_id',
        'property_id',
        'name',
        'phone',
        'email',
        'event_type',
        'event_date',
        'guest_count',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',
        ];
    }

    public function banquet(): BelongsTo
    {
        return $this->belongsTo(Banquet::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
