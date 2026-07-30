<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    use LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_CHECKED_OUT = 'checked_out';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_DIRECT = 'direct';

    public const SOURCE_PHONE = 'phone';

    public const SOURCE_WALK_IN = 'walk_in';

    public const SOURCE_ONLINE = 'online';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'booking_number',
        'property_id',
        'room_type_id',
        'room_id',
        'rate_plan_id',
        'user_id',
        'status',
        'source',
        'guest_name',
        'guest_email',
        'guest_phone',
        'check_in_date',
        'check_out_date',
        'nights',
        'adults',
        'children',
        'total_amount_minor',
        'discount_amount_minor',
        'discount_id',
        'discount_label',
        'corporate_id',
        'billing',
        'tax_rate_bp',
        'tax_amount_minor',
        'payment_status',
        'invoice_number',
        'currency',
        'special_requests',
        'internal_notes',
        'cancelled_at',
        'cancellation_policy_snapshot',
        'room_configuration_snapshot',
        'property_rules_snapshot',
        'property_rules_version',
        'cancellation_reason',
        'cancelled_by',
        'cancellation_fee_minor',
        'refund_due_minor',
        'refund_state',
        'booking_group_code',
    ];

    public const CANCELLED_BY_GUEST = 'guest';

    public const CANCELLED_BY_ADMIN = 'admin';

    public const REFUND_NONE = 'none';

    public const REFUND_PENDING = 'pending';

    public const REFUND_PROCESSED = 'processed';

    public const REFUND_FAILED = 'failed';

    public const REFUND_MANUAL = 'manual';

    public const BILLING_GUEST = 'guest';

    public const BILLING_CORPORATE = 'corporate';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'cancelled_at' => 'datetime',
            'nights' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'total_amount_minor' => 'integer',
            'discount_amount_minor' => 'integer',
            'tax_rate_bp' => 'integer',
            'tax_amount_minor' => 'integer',
            'cancellation_policy_snapshot' => 'array',
            'room_configuration_snapshot' => 'array',
            'property_rules_snapshot' => 'array',
            'property_rules_version' => 'integer',
            'cancellation_fee_minor' => 'integer',
            'refund_due_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if (! $booking->booking_number) {
                $booking->booking_number = static::nextBookingNumber();
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

    public function guests(): HasMany { return $this->hasMany(BookingGuest::class); }
    public function primaryGuest(): HasOne { return $this->hasOne(BookingGuest::class)->where('role', 'primary'); }
    public function stay(): HasOne { return $this->hasOne(Stay::class); }

    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    public function formattedTotal(): string
    {
        return $this->currency.' '.number_format($this->total_amount_minor / 100, 2);
    }

    /** Room tariff after discount, before GST. */
    public function netTariffMinor(): int
    {
        return max(0, $this->total_amount_minor - $this->discount_amount_minor);
    }

    /** Discounted room tariff plus GST — the amount actually payable. */
    public function grossTotalMinor(): int
    {
        return $this->netTariffMinor() + $this->tax_amount_minor;
    }

    public function formattedDiscount(): string
    {
        return $this->currency.' '.number_format($this->discount_amount_minor / 100, 2);
    }

    public function formattedGrossTotal(): string
    {
        return $this->currency.' '.number_format($this->grossTotalMinor() / 100, 2);
    }

    public function paidAmountMinor(): int
    {
        return (int) $this->payments
            ->where('status', Payment::STATUS_CAPTURED)
            ->sum(fn (Payment $payment) => max(0, $payment->amount_minor - $payment->refunded_amount_minor));
    }

    public function outstandingAmountMinor(): int
    {
        return max(0, $this->grossTotalMinor() - $this->paidAmountMinor());
    }

    public function paymentOptionLabel(): string
    {
        if ($this->billing === self::BILLING_CORPORATE) {
            return 'Bill to company';
        }

        $payment = $this->payments->sortByDesc('id')->first();

        if (! $payment) {
            return 'Pay at property';
        }

        if ($payment->method) {
            return match ($payment->method) {
                'cash' => 'Cash',
                'upi' => 'UPI',
                'card' => 'Card',
                'bank_transfer' => 'Bank transfer',
                default => str($payment->method)->replace('_', ' ')->title()->toString(),
            };
        }

        return match ($payment->gateway) {
            'razorpay' => str_starts_with((string) $payment->gateway_order_id, 'order_local_')
                ? 'Online · Sandbox'
                : 'Online · Razorpay',
            default => 'Online · '.str($payment->gateway)->title(),
        };
    }

    public static function nextInvoiceNumber(): string
    {
        return 'INV'.now()->format('ymd').Str::upper(Str::random(6));
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_CHECKED_IN => 'Checked in',
            self::STATUS_CHECKED_OUT => 'Checked out',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * @return list<string>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CHECKED_IN,
        ];
    }

    public static function nextBookingNumber(): string
    {
        return 'BK'.now()->format('ymd').Str::upper(Str::random(6));
    }
}
