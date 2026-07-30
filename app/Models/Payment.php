<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    public const STATUS_CREATED = 'created';

    public const STATUS_CAPTURED = 'captured';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'payment_number',
        'booking_id',
        'booking_group_code',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'gateway_refund_id',
        'method',
        'status',
        'amount_minor',
        'refunded_amount_minor',
        'currency',
        'paid_at',
        'refunded_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'refunded_amount_minor' => 'integer',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->payment_number ??= 'PAY'.now()->format('ymd').Str::upper(Str::random(6));
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format($this->amount_minor / 100, 2);
    }
}
