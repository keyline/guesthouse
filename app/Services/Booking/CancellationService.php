<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Discount;
use App\Models\Payment;
use App\Models\RatePlan;
use App\Models\Setting;
use App\Services\Payments\RazorpayGateway;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The single place cancellation terms are decided and applied. The refund a
 * guest is shown on the cancel screen, the amount the gateway is asked to
 * refund, and the amounts recorded on the booking all come from the same
 * quote() so they can never disagree.
 *
 * Terms are read from the policy snapshot frozen on the booking at creation;
 * bookings created before snapshots existed fall back to the old binary rule
 * (refundable plan + before check-in day = full refund).
 */
class CancellationService
{
    public function __construct(private RazorpayGateway $gateway)
    {
    }

    /**
     * The absolute check-in moment: check-in date at the property's default
     * check-in time (stored in minutes since midnight), in the site timezone.
     */
    public function checkInAt(CarbonInterface $checkInDate): CarbonImmutable
    {
        $minutes = (int) Setting::get('default_check_in_time', 840);

        return CarbonImmutable::parse($checkInDate->toDateString(), $this->timezone())
            ->startOfDay()
            ->addMinutes($minutes);
    }

    /**
     * Freeze the plan's policy into the snapshot stored on a new booking.
     *
     * @return array{policy_id: int, name: string, code: string, check_in_at: string,
     *     tiers: list<array{until: string, refund_percent: int}>}|null
     */
    public function snapshotFor(?RatePlan $plan, CarbonInterface $checkInDate): ?array
    {
        $policy = $plan?->effectiveCancellationPolicy();

        return $policy?->snapshotFor($this->checkInAt($checkInDate));
    }

    /**
     * What cancelling right now (or at $at) means for this booking's group:
     * the applicable refund percentage, the amount actually paid online, and
     * the split between refund due and cancellation fee.
     *
     * @param  Collection<int, Booking>  $groupBookings
     * @return array{refund_percent: int, paid_minor: int, refund_due_minor: int,
     *     fee_minor: int, payment: ?Payment}
     */
    public function quote(Booking $booking, Collection $groupBookings, ?CarbonInterface $at = null): array
    {
        $at = CarbonImmutable::parse($at ?? now())->setTimezone($this->timezone());
        $percent = $this->refundPercentAt($booking, $groupBookings, $at);

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('status', Payment::STATUS_CAPTURED)
            ->latest('id')
            ->first();

        $paid = $payment?->amount_minor ?? 0;
        $refund = intdiv($paid * $percent, 100);

        return [
            'refund_percent' => $percent,
            'paid_minor' => $paid,
            'refund_due_minor' => $refund,
            'fee_minor' => $paid - $refund,
            'payment' => $payment,
        ];
    }

    /**
     * Cancel the whole group: statuses, inventory, discount release, the
     * accountability trail (who, why, how much), and the gateway refund.
     * An explicit override (admin only, validated by the caller) replaces the
     * policy amount and is capped at what was actually paid.
     *
     * @param  Collection<int, Booking>  $groupBookings
     * @return array{quote: array{refund_percent: int, paid_minor: int, refund_due_minor: int,
     *     fee_minor: int, payment: ?Payment}, refund_state: string, refunded_minor: int}
     */
    public function cancel(
        Booking $booking,
        Collection $groupBookings,
        InventoryService $inventory,
        string $cancelledBy,
        ?string $reason = null,
        ?int $overrideRefundMinor = null,
    ): array {
        $quote = $this->quote($booking, $groupBookings);

        if ($overrideRefundMinor !== null) {
            $quote['refund_due_minor'] = min(max(0, $overrideRefundMinor), $quote['paid_minor']);
            $quote['fee_minor'] = $quote['paid_minor'] - $quote['refund_due_minor'];
        }

        $payment = $quote['payment'];
        $refundState = $payment && $quote['refund_due_minor'] > 0
            ? Booking::REFUND_PENDING
            : Booking::REFUND_NONE;

        DB::transaction(function () use ($booking, $groupBookings, $inventory, $cancelledBy, $reason, $quote, $payment, $refundState): void {
            // Serialize on the payment row so a guest and an admin cancelling
            // simultaneously cannot both issue the refund.
            if ($payment) {
                $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();

                if ($payment->status !== Payment::STATUS_CAPTURED) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'booking' => 'This booking is already being cancelled or refunded.',
                    ]);
                }
            }

            foreach ($groupBookings as $room) {
                $room->update([
                    'status' => Booking::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                    'cancelled_by' => $cancelledBy,
                    // Money fields live on the booking that carries the
                    // payment; the rest of the group stays at zero/none.
                    'cancellation_fee_minor' => $room->is($booking) ? $quote['fee_minor'] : 0,
                    'refund_due_minor' => $room->is($booking) ? $quote['refund_due_minor'] : 0,
                    'refund_state' => $room->is($booking) ? $refundState : Booking::REFUND_NONE,
                ]);
                $inventory->syncBooking($room);
            }

            Discount::releaseFor($booking);
        });

        $refundedMinor = 0;

        if ($refundState === Booking::REFUND_PENDING) {
            $refundState = $this->processRefund($booking, $groupBookings, $payment, $quote['refund_due_minor']);
            $refundedMinor = $refundState === Booking::REFUND_PROCESSED ? $quote['refund_due_minor'] : 0;
        }

        return [
            'quote' => $quote,
            'refund_state' => $refundState,
            'refunded_minor' => $refundedMinor,
        ];
    }

    /**
     * Mark a failed/manual refund as settled outside the gateway (bank
     * transfer, cash). Records who did it in the reason trail via LogsActivity.
     */
    public function markManuallyRefunded(Booking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $booking->update(['refund_state' => Booking::REFUND_PROCESSED]);

            Payment::query()
                ->where('booking_id', $booking->id)
                ->where('status', Payment::STATUS_CAPTURED)
                ->latest('id')
                ->first()?->update([
                    'status' => Payment::STATUS_REFUNDED,
                    'refunded_amount_minor' => $booking->refund_due_minor,
                    'refunded_at' => now(),
                ]);

            $this->groupQuery($booking)->update(['payment_status' => Booking::PAYMENT_REFUNDED]);
        });
    }

    /**
     * Attempt the gateway refund; on failure leave a 'failed' marker for the
     * pending-refunds queue instead of losing the debt.
     */
    private function processRefund(Booking $booking, Collection $groupBookings, Payment $payment, int $refundMinor): string
    {
        if (! $payment->gateway_payment_id) {
            $booking->update(['refund_state' => Booking::REFUND_MANUAL]);

            return Booking::REFUND_MANUAL;
        }

        try {
            $refund = $this->gateway->refund($payment->gateway_payment_id, $refundMinor);
        } catch (\RuntimeException) {
            $booking->update(['refund_state' => Booking::REFUND_FAILED]);

            return Booking::REFUND_FAILED;
        }

        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'gateway_refund_id' => $refund['id'] ?? null,
            'refunded_amount_minor' => $refundMinor,
            'refunded_at' => now(),
        ]);

        Booking::query()
            ->whereKey($groupBookings->pluck('id'))
            ->update(['payment_status' => Booking::PAYMENT_REFUNDED]);

        $booking->update(['refund_state' => Booking::REFUND_PROCESSED]);

        return Booking::REFUND_PROCESSED;
    }

    /**
     * The refund percentage the policy grants at $at. Snapshot tiers when we
     * have them, the legacy binary rule otherwise. A group refunds at the
     * least generous member's percentage — one policy per payment.
     *
     * @param  Collection<int, Booking>  $groupBookings
     */
    private function refundPercentAt(Booking $booking, Collection $groupBookings, CarbonImmutable $at): int
    {
        return $groupBookings
            ->map(function (Booking $room) use ($at): int {
                $snapshot = $room->cancellation_policy_snapshot;

                if (! is_array($snapshot)) {
                    return $this->legacyRefundPercent($room, $at);
                }

                foreach ($snapshot['tiers'] ?? [] as $tier) {
                    if ($at->lessThan(CarbonImmutable::parse($tier['until']))) {
                        return (int) $tier['refund_percent'];
                    }
                }

                return 0;
            })
            ->min() ?? 0;
    }

    /** Pre-snapshot bookings keep the rule they were sold under. */
    private function legacyRefundPercent(Booking $booking, CarbonImmutable $at): int
    {
        $refundable = (bool) $booking->ratePlan?->is_refundable
            && $at->lessThan(CarbonImmutable::parse($booking->check_in_date->toDateString(), $this->timezone()));

        return $refundable ? 100 : 0;
    }

    private function groupQuery(Booking $booking)
    {
        return Booking::query()->where(fn ($query) => $booking->booking_group_code
            ? $query->where('booking_group_code', $booking->booking_group_code)
            : $query->whereKey($booking->id));
    }

    private function timezone(): string
    {
        return (string) Setting::get('timezone', config('app.timezone', 'Asia/Kolkata'));
    }
}
