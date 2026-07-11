<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\RazorpayWebhook;
use App\Models\RazorpayOrder;
use App\Services\RazorpayService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpayService,
        protected SubscriptionService $subscriptionService
    ) {
    }

    /**
     * Handle Razorpay webhooks (public endpoint).
     */
    public function razorpay(Request $request)
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true) ?: [];

        $signature = $request->header('X-Razorpay-Signature') ?? $request->header('x-razorpay-signature');

        if ($this->razorpayService->isConfigured() && config('services.razorpay.webhook_secret')) {
            if (!$signature || !$this->razorpayService->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Razorpay webhook signature mismatch');
                return response('Invalid signature', 400);
            }
        }

        $eventId = $data['id'] ?? $data['payload']['payment']['entity']['id'] ?? $data['payload']['invoice']['entity']['id'] ?? null;
        $eventType = $data['event'] ?? $data['event_type'] ?? null;

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
            if (str_starts_with((string) $eventType, 'payment')) {
                $payment = $data['payload']['payment']['entity'] ?? [];
                $orderId = $payment['order_id'] ?? null;
                $paymentId = $payment['id'] ?? null;

                if ($orderId && $paymentId) {
                    $razorOrder = RazorpayOrder::where('razorpay_order_id', $orderId)->first();
                    if ($razorOrder && !$razorOrder->isPaid()) {
                        $this->subscriptionService->fulfillPaidOrder($razorOrder, $paymentId, $payment);
                    }
                }
            }

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
