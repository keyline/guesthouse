<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private ?\App\Models\User $bookingCustomer = null;

    private function customer(): \App\Models\User
    {
        return $this->bookingCustomer ??= \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_CUSTOMER, 'is_active' => true]);
    }

    public function test_gst_uses_low_slab_below_tariff_threshold(): void
    {
        [, , $plan] = $this->sellableSetup(); // ₹1,800/night → 5% slab

        $this->reserve($plan, paymentMode: 'pay_at_property');

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(500, $booking->tax_rate_bp);
        $this->assertSame((int) round(180000 * 0.05), $booking->tax_amount_minor);
        $this->assertSame(180000 + 9000, $booking->grossTotalMinor());
    }

    public function test_gst_uses_high_slab_above_tariff_threshold(): void
    {
        [, , $plan] = $this->sellableSetup(nightlyMinor: 800000); // ₹8,000/night → 18% slab

        $this->reserve($plan, paymentMode: 'pay_at_property');

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(1800, $booking->tax_rate_bp);
        $this->assertSame((int) round(800000 * 0.18), $booking->tax_amount_minor);
    }

    public function test_pay_online_creates_razorpay_order_and_payment_record(): void
    {
        [, , $plan] = $this->sellableSetup();
        Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_TEST123', 'amount' => 189000, 'currency' => 'INR'])]);

        $this->reserve($plan)->assertRedirect();
        $booking = Booking::query()->firstOrFail();

        $this->get(route('book.pay', ['bookingNumber' => $booking->booking_number]))
            ->assertOk()
            ->assertSee('Complete your payment')
            ->assertSee('order_TEST123')
            ->assertSee('1,890.00'); // ₹1,800 tariff + 5% GST

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'gateway_order_id' => 'order_TEST123',
            'status' => Payment::STATUS_CREATED,
            'amount_minor' => 189000,
        ]);
    }

    public function test_local_sandbox_creates_a_simulated_order_without_calling_razorpay(): void
    {
        app()->detectEnvironment(fn () => 'local');

        try {
            config()->set('services.razorpay.mode', 'local_sandbox');
            Http::fake();

            $order = app(\App\Services\Payments\RazorpayGateway::class)
                ->createOrder(189000, 'INR', 'BK-LOCAL-TEST');

            Http::assertNothingSent();
            $this->assertTrue($order['sandbox']);
            $this->assertSame(189000, $order['amount']);
            $this->assertStringStartsWith('order_local_', $order['id']);
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_verified_payment_confirms_booking_and_issues_invoice(): void
    {
        $booking = $this->paidOrderSetup();

        $signature = hash_hmac('sha256', 'order_TEST123|pay_TEST456', config('services.razorpay.key_secret'));

        $this->post(route('book.pay.verify'), [
            'razorpay_order_id' => 'order_TEST123',
            'razorpay_payment_id' => 'pay_TEST456',
            'razorpay_signature' => $signature,
        ])->assertRedirect(route('book.confirmation', ['bookingNumber' => $booking->booking_number]));

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);
        $this->assertSame(Booking::PAYMENT_PAID, $booking->payment_status);
        $this->assertNotNull($booking->invoice_number);

        $this->assertDatabaseHas('payments', [
            'gateway_order_id' => 'order_TEST123',
            'gateway_payment_id' => 'pay_TEST456',
            'status' => Payment::STATUS_CAPTURED,
        ]);
    }

    public function test_tampered_signature_marks_payment_failed(): void
    {
        $booking = $this->paidOrderSetup();

        $this->post(route('book.pay.verify'), [
            'razorpay_order_id' => 'order_TEST123',
            'razorpay_payment_id' => 'pay_TEST456',
            'razorpay_signature' => 'forged-signature',
        ])->assertSessionHasErrors('payment');

        $booking->refresh();
        $this->assertSame(Booking::STATUS_PENDING, $booking->status);
        $this->assertSame(Booking::PAYMENT_UNPAID, $booking->payment_status);
        $this->assertDatabaseHas('payments', ['gateway_order_id' => 'order_TEST123', 'status' => Payment::STATUS_FAILED]);
    }

    public function test_refundable_paid_booking_is_cancelled_with_refund(): void
    {
        $booking = $this->paidOrderSetup(checkInDaysFromNow: 3);
        $this->capturePayment($booking);
        Http::fake(['api.razorpay.com/v1/payments/pay_TEST456/refund' => Http::response(['id' => 'rfnd_TEST789'])]);

        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), [
            'guest_phone' => '+91 98000 00000',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertSame(Booking::PAYMENT_REFUNDED, $booking->payment_status);
        $this->assertDatabaseHas('payments', [
            'gateway_payment_id' => 'pay_TEST456',
            'status' => Payment::STATUS_REFUNDED,
            'gateway_refund_id' => 'rfnd_TEST789',
        ]);
    }

    public function test_non_refundable_booking_cancels_without_refund(): void
    {
        $booking = $this->paidOrderSetup(checkInDaysFromNow: 3, refundable: false);
        $this->capturePayment($booking);
        Http::fake(); // any refund call would be recorded

        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), [
            'guest_phone' => '+91 98000 00000',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertSame(Booking::PAYMENT_PAID, $booking->payment_status);
        Http::assertNothingSent();
        $this->assertDatabaseHas('payments', ['gateway_payment_id' => 'pay_TEST456', 'status' => Payment::STATUS_CAPTURED]);
    }

    public function test_cancellation_requires_matching_phone(): void
    {
        [, , $plan] = $this->sellableSetup();
        $this->reserve($plan, paymentMode: 'pay_at_property');
        $booking = Booking::query()->firstOrFail();

        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), [
            'guest_phone' => '+91 90000 99999',
        ])->assertSessionHasErrors('guest_phone');

        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_cancellation_releases_inventory(): void
    {
        [$property, $roomType, $plan] = $this->sellableSetup();
        $this->reserve($plan, paymentMode: 'pay_at_property', checkInDaysFromNow: 3);
        $booking = Booking::query()->firstOrFail();

        $this->post(route('book.cancel.store', ['bookingNumber' => $booking->booking_number]), [
            'guest_phone' => '+91 98000 00000',
        ])->assertRedirect();

        $this->assertDatabaseHas('room_type_inventory', [
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'rooms_sold' => 0,
        ]);
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

    private function paidOrderSetup(int $checkInDaysFromNow = 0, bool $refundable = true): Booking
    {
        [, , $plan] = $this->sellableSetup(refundable: $refundable);
        Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_TEST123'])]);
        $this->reserve($plan, checkInDaysFromNow: $checkInDaysFromNow);
        $booking = Booking::query()->firstOrFail();
        $this->get(route('book.pay', ['bookingNumber' => $booking->booking_number]));

        return $booking;
    }

    private function capturePayment(Booking $booking): void
    {
        $signature = hash_hmac('sha256', 'order_TEST123|pay_TEST456', config('services.razorpay.key_secret'));
        $this->post(route('book.pay.verify'), [
            'razorpay_order_id' => 'order_TEST123',
            'razorpay_payment_id' => 'pay_TEST456',
            'razorpay_signature' => $signature,
        ]);
    }

    /**
     * @return array{0: Property, 1: RoomType, 2: RatePlan}
     */
    private function sellableSetup(int $nightlyMinor = 180000, bool $refundable = true): array
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
            'is_refundable' => $refundable, 'default_price_minor' => $nightlyMinor,
            'currency' => 'INR', 'status' => RatePlan::STATUS_ACTIVE,
        ]);

        return [$property, $roomType, $plan];
    }
}
