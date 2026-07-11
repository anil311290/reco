<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->acceptJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => 'INR',
                'receipt' => $receipt,
                'notes' => $notes,
            ]);

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

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->acceptJson()
            ->get('https://api.razorpay.com/v1/payments/' . $paymentId);

        return $response->successful() ? $response->json() : null;
    }

    protected function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env');
        }
    }
}
