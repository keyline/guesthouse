<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanquetPrice extends Model
{
    protected $fillable = [
        'banquet_id',
        'season',
        'price_per_person',
        'date_from',
        'date_to',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price_per_person' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
        ];
    }

    public function banquet(): BelongsTo
    {
        return $this->belongsTo(Banquet::class);
    }
}
