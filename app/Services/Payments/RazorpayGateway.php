<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Thin Razorpay REST client. Uses the key pair from config/services.php —
 * ship with dummy rzp_test keys and swap in real test/live keys via .env.
 */
class RazorpayGateway
{
    private const BASE_URL = 'https://api.razorpay.com/v1';

    public function keyId(): string
    {
        return (string) config('services.razorpay.key_id');
    }

    public function isLocalSandbox(): bool
    {
        return app()->environment('local')
            && config('services.razorpay.mode') === 'local_sandbox';
    }

    /**
     * @return array<string, mixed> the Razorpay order (id, amount, currency, …)
     */
    public function createOrder(int $amountMinor, string $currency, string $receipt): array
    {
        if ($this->isLocalSandbox()) {
            return [
                'id' => 'order_local_'.Str::lower(Str::random(18)),
                'amount' => $amountMinor,
                'currency' => $currency,
                'receipt' => $receipt,
                'sandbox' => true,
            ];
        }

        $response = Http::withBasicAuth($this->keyId(), (string) config('services.razorpay.key_secret'))
            ->post(self::BASE_URL.'/orders', [
                'amount' => $amountMinor,
                'currency' => $currency,
                'receipt' => $receipt,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Could not start the online payment. Please try again or pay at the property.');
        }

        return $response->json();
    }

    /**
     * Razorpay checkout returns a signature over "order_id|payment_id" — verify
     * it with the key secret before trusting the payment.
     */
    public function signatureIsValid(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, (string) config('services.razorpay.key_secret'));

        return hash_equals($expected, $signature);
    }

    /**
     * @return array<string, mixed> the Razorpay refund
     */
    public function refund(string $paymentId, int $amountMinor): array
    {
        if ($this->isLocalSandbox() && str_starts_with($paymentId, 'pay_local_')) {
            return ['id' => 'rfnd_local_'.Str::lower(Str::random(18)), 'amount' => $amountMinor, 'sandbox' => true];
        }

        $response = Http::withBasicAuth($this->keyId(), (string) config('services.razorpay.key_secret'))
            ->post(self::BASE_URL.'/payments/'.$paymentId.'/refund', [
                'amount' => $amountMinor,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('The refund could not be processed automatically. The property will process it manually.');
        }

        return $response->json();
    }
}
