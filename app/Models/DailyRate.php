<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyRate extends Model
{
    use LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'rate_plan_id',
        'date',
        'price_minor',
        'min_stay',
        'closed',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'price_minor' => 'integer',
            'min_stay' => 'integer',
            'closed' => 'boolean',
        ];
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }
}
