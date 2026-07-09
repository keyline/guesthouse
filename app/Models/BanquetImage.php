<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanquetImage extends Model
{
    protected $fillable = [
        'banquet_id',
        'path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function banquet(): BelongsTo
    {
        return $this->belongsTo(Banquet::class);
    }
}
