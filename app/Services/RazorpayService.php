<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class RazorpayService
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->keyId = (string) config('services.razorpay.key_id', '');
        $this->keySecret = (string) config('services.razorpay.key_secret', '');
        $this->webhookSecret = (string) config('services.razorpay.webhook_secret', '');
    }

    public function isConfigured(): bool
    {
        return $this->keyId !== '' && $this->keySecret !== '';
    }

    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * Create a Razorpay order. Amount is in INR rupees; API expects paise.
     */
    public function createOrder(float $amountInr, string $receipt, array $notes = []): array
    {
        $this->ensureConfigured();

        $amountPaise = (int) round($amountInr * 100);
        if ($amountPaise < 100) {
            throw new RuntimeException('Payment amount must be at least ₹1.');
        }

        try {
            $response = $this->request()
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => $amountPaise,
                    'currency' => 'INR',
                    'receipt' => $receipt,
                    'notes' => $notes,
                ]);
        } catch (ConnectionException $exception) {
            Log::error('Razorpay order request timed out', [
                'receipt' => $receipt,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Razorpay is temporarily unreachable. Please check your internet connection and try again.'
            );
        }

        if (!$response->successful()) {
            Log::error('Razorpay order creation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new RuntimeException($response->json('error.description') ?? 'Unable to create Razorpay order.');
        }

        return $response->json();
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $this->ensureConfigured();

        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);

        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (!$this->webhookSecret || !$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function fetchPayment(string $paymentId): ?array
    {
        $this->ensureConfigured();

        try {
            $response = $this->request()
                ->get('https://api.razorpay.com/v1/payments/' . $paymentId);
        } catch (ConnectionException $exception) {
            Log::error('Razorpay payment lookup timed out', [
                'payment_id' => $paymentId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Build a resilient Razorpay HTTP client without disabling TLS verification.
     */
    protected function request()
    {
        return Http::withBasicAuth($this->keyId, $this->keySecret)
            ->acceptJson()
            ->connectTimeout((int) config('services.razorpay.connect_timeout', 15))
            ->timeout((int) config('services.razorpay.timeout', 30))
            ->retry(
                (int) config('services.razorpay.retry_times', 2),
                (int) config('services.razorpay.retry_sleep', 1000),
                fn ($exception) => $exception instanceof ConnectionException
            );
    }

    protected function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env');
        }
    }
}
