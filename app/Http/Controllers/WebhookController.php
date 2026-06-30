<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\RazorpayWebhook;
use App\Models\RazorpayOrder;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Razorpay webhooks (public endpoint).
     */
    public function razorpay(Request $request)
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true) ?: [];

        $signature = $request->header('X-Razorpay-Signature') ?? $request->header('x-razorpay-signature');
        $secret = env('RAZORPAY_WEBHOOK_SECRET');

        // Basic signature verification if secret configured
        if ($secret && $signature) {
            $computed = hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($computed, $signature)) {
                Log::warning('Razorpay webhook signature mismatch');
                return response('Invalid signature', 400);
            }
        }

        // Extract event id for idempotency
        $eventId = $data['id'] ?? $data['payload']['payment']['entity']['id'] ?? $data['payload']['invoice']['entity']['id'] ?? null;
        $eventType = $data['event'] ?? $data['event_type'] ?? null;

        // Save webhook record (avoid duplicates)
        $existing = $eventId ? RazorpayWebhook::where('razorpay_event_id', $eventId)->first() : null;
        if ($existing && $existing->isProcessed()) {
            return response('OK', 200);
        }

        $webhook = $existing ?? new RazorpayWebhook();
        $webhook->event_type = $eventType;
        $webhook->razorpay_event_id = $eventId;
        $webhook->payload = $data;
        $webhook->status = 'received';
        $webhook->save();

        try {
            // Basic handlers
            if (str_starts_with((string)$eventType, 'payment')) {
                $payment = $data['payload']['payment']['entity'] ?? [];
                $orderId = $payment['order_id'] ?? null;
                $paymentId = $payment['id'] ?? null;
                $amount = isset($payment['amount']) ? ($payment['amount'] / 100) : null;
                $currency = $payment['currency'] ?? 'INR';

                if ($orderId && $paymentId) {
                    $razorOrder = RazorpayOrder::where('razorpay_order_id', $orderId)->first();
                    if ($razorOrder) {
                        // create payment record if not exists
                        $existingPayment = SubscriptionPayment::where('razorpay_payment_id', $paymentId)->first();
                        if (!$existingPayment) {
                            SubscriptionPayment::create([
                                'company_id' => $razorOrder->company_id,
                                'subscription_id' => $razorOrder->subscription_id,
                                'razorpay_payment_id' => $paymentId,
                                'razorpay_order_id' => $orderId,
                                'amount' => $amount,
                                'currency' => $currency,
                                'status' => 'completed',
                                'payment_method' => $payment['method'] ?? null,
                                'gateway_response' => $payment,
                                'paid_at' => now(),
                            ]);
                        }

                        $razorOrder->update(['status' => 'paid']);
                    }
                }
            }

            // Mark processed
            $webhook->status = 'processed';
            $webhook->processed_at = now();
            $webhook->save();

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            $webhook->status = 'failed';
            $webhook->error_message = $e->getMessage();
            $webhook->save();
            return response('Error', 500);
        }
    }
}
