<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\CancellationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CancellationPolicyModuleTest extends TestCase
{
    use RefreshDatabase;

    private ?User $bookingCustomer = null;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -------------------------------------------------- policy templates

    public function test_default_policy_templates_are_seeded(): void
    {
        $this->assertNotNull(CancellationPolicy::byCode(CancellationPolicy::CODE_FLEXIBLE));
        $this->assertNotNull(CancellationPolicy::byCode(CancellationPolicy::CODE_FREE_24H));
        $this->assertNotNull(CancellationPolicy::byCode(CancellationPolicy::CODE_NON_REFUNDABLE));
    }

    public function test_booking_freezes_policy_snapshot_at_creation(): void
    {
        [, , $plan] = $this->sellableSetup();

        $this->reserve($plan, checkInDaysFromNow: 5);

        $booking = Booking::query()->firstOrFail();
        $snapshot = $booking->cancellation_policy_snapshot;

        $this->assertIsArray($snapshot);
        $this->assertSame(CancellationPolicy::CODE_FLEXIBLE, $snapshot['code']);
        $this->assertCount(2, $snapshot['tiers']);
        $this->assertSame(100, $snapshot['tiers'][0]['refund_percent']);
        $this->assertSame(50, $snapshot['tiers'][1]['refund_percent']);
    }

    public function test_editing_a_policy_template_does_not_change_an_existing_booking(): void
    {
        [, , $plan] = $this->sellableSetup();
        $this->reserve($plan, checkInDaysFromNow: 5);
        $booking = Booking::query()->firstOrFail();

        CancellationPolicy::byCode(CancellationPolicy::CODE_FLEXIBLE)->update(['tiers' => []]);

        $service = app(CancellationService::class);
        $quote = $service->quote($booking, collect([$booking]));

        // Still the generous terms the guest was sold, not the gutted template.
        $this->assertSame(100, $quote['refund_percent']);
    }

    // -------------------------------------------------- tier boundaries

    public function test_refund_percentage_follows_the_snapshot_tiers(): void
    {
        $booking = $this->paidBooking(checkInDaysFromNow: 5);
        $checkInAt = CarbonImmutable::parse($booking->cancellation_policy_snapshot['check_in_at']);
        $service = app(CancellationService::class);
        $group = collect([$booking]);

        // Flexible: ≥24h before → 100%, ≥6h → 50%, after → 0%.
        $this->assertSame(100, $service->quote($booking, $group, $checkInAt->subHours(30))['refund_percent']);
        $this->assertSame(50, $service->quote($booking, $group, $checkInAt->subHours(12))['refund_percent']);
        $this->assertSame(0, $service->quote($booking, $group, $checkInAt->subHours(2))['refund_percent']);

        // Refund amounts follow the percentage of the amount actually paid.
        $paid = $service->quote($booking, $group, $checkInAt->subHours(12))['paid_minor'];
        $this->assertSame(intdiv($paid, 2), $service->quote($booking, $group, $checkInAt->subHours(12))['refund_due_minor']);
    }

    // -------------------------------------------------- guest cancellation

    public function test_guest_gets_partial_refund_inside_the_50_percent_window(): void
    {
        $booking = $this->paidBooking(checkInDaysFromNow: 5);
        $paid = Payment::query()->where('status', Payment::STATUS_CAPTURED)->firstOrFail()->amount_minor;

        $checkInAt = CarbonImmutable::parse($booking->cancellation_policy_snapshot['check_in_at']);
        Carbon::setTestNow($checkInAt->subHours(12));

        Http::fake(['api.razorpay.com/v1/payments/pay_TEST456/refund' => Http::response(['id' => 'rfnd_TEST789'])]);

        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), [
            'guest_phone' => '+91 98000 00000',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertSame(Booking::CANCELLED_BY_GUEST, $booking->cancelled_by);
        $this->assertSame(Booking::REFUND_PROCESSED, $booking->refund_state);
        $this->assertSame(intdiv($paid, 2), $booking->refund_due_minor);
        $this->assertSame($paid - intdiv($paid, 2), $booking->cancellation_fee_minor);

        $payment = Payment::query()->firstOrFail();
        $this->assertSame(Payment::STATUS_REFUNDED, $payment->status);
        $this->assertSame(intdiv($paid, 2), $payment->refunded_amount_minor);
    }

    public function test_failed_gateway_refund_lands_in_the_pending_queue(): void
    {
        $booking = $this->paidBooking(checkInDaysFromNow: 5);

        Http::fake(['api.razorpay.com/v1/payments/pay_TEST456/refund' => Http::response(['error' => 'boom'], 500)]);

        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), [
            'guest_phone' => '+91 98000 00000',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(Booking::REFUND_FAILED, $booking->refund_state);
        // The debt is not lost: the payment stays captured until settled.
        $this->assertSame(Payment::STATUS_CAPTURED, Payment::query()->firstOrFail()->status);

        $this->actingAs($this->admin())
            ->get(route('admin.bookings.pending-refunds'))
            ->assertOk()
            ->assertSee($booking->booking_number);
    }

    public function test_admin_can_settle_a_failed_refund_manually(): void
    {
        $booking = $this->paidBooking(checkInDaysFromNow: 5);
        Http::fake(['api.razorpay.com/v1/payments/pay_TEST456/refund' => Http::response([], 500)]);
        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), ['guest_phone' => '+91 98000 00000']);

        $this->actingAs($this->admin())
            ->post(route('admin.bookings.settle-refund', $booking->fresh()))
            ->assertRedirect(route('admin.bookings.pending-refunds'));

        $this->assertSame(Booking::REFUND_PROCESSED, $booking->fresh()->refund_state);
        $this->assertSame(Payment::STATUS_REFUNDED, Payment::query()->firstOrFail()->status);
        $this->assertSame(Booking::PAYMENT_REFUNDED, $booking->fresh()->payment_status);
    }

    // -------------------------------------------------- admin cancellation

    public function test_admin_cancellation_requires_a_reason_and_records_the_trail(): void
    {
        [, , $plan] = $this->sellableSetup();
        $this->reserve($plan, paymentMode: 'pay_at_property', checkInDaysFromNow: 5);
        $booking = Booking::query()->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.bookings.destroy', $booking))
            ->assertSessionHasErrors('cancellation_reason');

        $this->actingAs($admin)
            ->delete(route('admin.bookings.destroy', $booking), [
                'cancellation_reason' => 'no_show',
                'reason_note' => 'Called twice, no answer',
            ])
            ->assertRedirect(route('admin.bookings.index'));

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertSame(Booking::CANCELLED_BY_ADMIN, $booking->cancelled_by);
        $this->assertSame('No-show — Called twice, no answer', $booking->cancellation_reason);
    }

    public function test_only_super_admin_may_override_the_refund_amount(): void
    {
        $booking = $this->paidBooking(checkInDaysFromNow: 5);

        $manager = User::factory()->create(['role' => User::ROLE_PROPERTY_MANAGER, 'is_active' => true]);
        $manager->managedProperties()->attach($booking->property_id);
        $this->actingAs($manager)
            ->delete(route('admin.bookings.destroy', $booking), [
                'cancellation_reason' => 'other',
                'refund_override' => '100.00',
            ])
            ->assertForbidden();

        Http::fake(['api.razorpay.com/v1/payments/pay_TEST456/refund' => Http::response(['id' => 'rfnd_OVERRIDE'])]);

        $this->actingAs($this->admin())
            ->delete(route('admin.bookings.destroy', $booking), [
                'cancellation_reason' => 'property_issue',
                'refund_override' => '100.00',
            ])
            ->assertRedirect(route('admin.bookings.index'));

        $booking->refresh();
        $this->assertSame(10000, $booking->refund_due_minor);
        $this->assertSame(10000, Payment::query()->firstOrFail()->refunded_amount_minor);
    }

    // -------------------------------------------------- policy management

    public function test_admin_can_manage_policy_templates(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.cancellation-policies.index'))
            ->assertOk()
            ->assertSee('Flexible')
            ->assertSee('Non-refundable');

        $this->actingAs($admin)
            ->post(route('admin.cancellation-policies.store'), [
                'name' => 'Festive season',
                'tiers' => [
                    ['hours_before' => 72, 'refund_percent' => 100],
                    ['hours_before' => 24, 'refund_percent' => 25],
                ],
            ])
            ->assertRedirect(route('admin.cancellation-policies.index'));

        $policy = CancellationPolicy::query()->where('name', 'Festive season')->firstOrFail();
        $this->assertSame(
            [['hours_before' => 72, 'refund_percent' => 100], ['hours_before' => 24, 'refund_percent' => 25]],
            $policy->sortedTiers(),
        );
    }

    // -------------------------------------------------- fixtures

    private function customer(): User
    {
        return $this->bookingCustomer ??= User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
    }

    private function reserve(RatePlan $plan, string $paymentMode = 'pay_online', int $checkInDaysFromNow = 0)
    {
        return $this->actingAs($this->customer())->post('/book', [
            'rooms' => [$plan->id => 1],
            'check_in' => now()->addDays($checkInDaysFromNow)->toDateString(),
            'check_out' => now()->addDays($checkInDaysFromNow + 1)->toDateString(),
            'guest_name' => 'Priya Sharma',
            'guest_phone' => '+91 98000 00000',
            'guest_email' => 'priya@example.com',
            'adults' => 2,
            'children' => 0,
            'payment_mode' => $paymentMode,
        ]);
    }

    /** A confirmed booking with a captured online payment. */
    private function paidBooking(int $checkInDaysFromNow): Booking
    {
        [, , $plan] = $this->sellableSetup();
        Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_TEST123'])]);
        $this->reserve($plan, checkInDaysFromNow: $checkInDaysFromNow);
        $booking = Booking::query()->firstOrFail();
        $this->get(route('book.pay', ['bookingNumber' => $booking->booking_number]));

        $signature = hash_hmac('sha256', 'order_TEST123|pay_TEST456', config('services.razorpay.key_secret'));
        $this->post(route('book.pay.verify'), [
            'razorpay_order_id' => 'order_TEST123',
            'razorpay_payment_id' => 'pay_TEST456',
            'razorpay_signature' => $signature,
        ]);

        return $booking->fresh();
    }

    /**
     * @return array{0: Property, 1: RoomType, 2: RatePlan}
     */
    private function sellableSetup(int $nightlyMinor = 180000): array
    {
        $property = Property::query()->create([
            'name' => 'Central Guest House', 'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE, 'city' => 'Kolkata', 'country' => 'India',
            'address' => '12 Guest Road', 'base_price_minor' => $nightlyMinor, 'currency' => 'INR',
            'gstin' => '19AAACE1234F1Z5',
        ]);

        $roomType = RoomType::query()->create([
            'name' => 'Deluxe Double', 'code' => 'DLX', 'status' => RoomType::STATUS_ACTIVE,
            'max_adults' => 2, 'max_children' => 1, 'sort_order' => 0,
        ]);

        Room::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'room_number' => '101', 'status' => Room::STATUS_AVAILABLE, 'is_online_bookable' => true,
        ]);

        $plan = RatePlan::query()->create([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'name' => 'Standard Rate (EP)', 'code' => 'STD-EP', 'meal_plan' => RatePlan::MEAL_PLAN_EP,
            'is_refundable' => true, 'default_price_minor' => $nightlyMinor,
            'currency' => 'INR', 'status' => RatePlan::STATUS_ACTIVE,
            'cancellation_policy_id' => CancellationPolicy::byCode(CancellationPolicy::CODE_FLEXIBLE)?->id,
        ]);

        return [$property, $roomType, $plan];
    }
}
