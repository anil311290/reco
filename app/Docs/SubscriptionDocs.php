<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/plans",
 *     tags={"Subscriptions"},
 *     summary="List public subscription plans for registration",
 *     description="No auth required. Returns active and visible plans for the registration plan picker.",
 *     operationId="getPublicSubscriptionPlans",
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="slug", type="string", example="trial"),
 *                 @OA\Property(property="name", type="string", example="Trial"),
 *                 @OA\Property(property="monthly_price", type="number", example=0),
 *                 @OA\Property(property="yearly_price", type="number", example=0),
 *                 @OA\Property(property="trial_days", type="integer", example=14),
 *                 @OA\Property(property="description", type="string"),
 *                 @OA\Property(property="features", type="array", @OA\Items(type="string"))
 *             ))
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/subscriptions/plans",
 *     tags={"Subscriptions"},
 *     summary="List subscription plans",
 *     operationId="getSubscriptionPlans",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/subscriptions/current",
 *     tags={"Subscriptions"},
 *     summary="Get current subscription",
 *     operationId="getCurrentSubscription",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/subscriptions/subscribe",
 *     tags={"Subscriptions"},
 *     summary="Subscribe to a plan (free trial or Razorpay checkout)",
 *     operationId="subscribe",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"plan_id","billing_cycle"},
 *         @OA\Property(property="plan_id", type="integer", example=2),
 *         @OA\Property(property="billing_cycle", type="string", enum={"monthly","yearly","lifetime"}, example="monthly")
 *     )),
 *     @OA\Response(
 *         response=200,
 *         description="Free plan activated or checkout required",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="requires_payment", type="boolean", example=true),
 *                 @OA\Property(property="checkout", type="object",
 *                     @OA\Property(property="key_id", type="string", example="rzp_test_xxxxx"),
 *                     @OA\Property(property="order_id", type="string", example="order_xxxxx"),
 *                     @OA\Property(property="amount_paise", type="integer", example=49900),
 *                     @OA\Property(property="currency", type="string", example="INR"),
 *                     @OA\Property(property="description", type="string"),
 *                     @OA\Property(property="plan_name", type="string"),
 *                     @OA\Property(property="user_name", type="string"),
 *                     @OA\Property(property="user_email", type="string")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=201, description="Free subscription created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/subscriptions/verify-payment",
 *     tags={"Subscriptions"},
 *     summary="Verify Razorpay payment and activate subscription",
 *     operationId="verifySubscriptionPayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"razorpay_order_id","razorpay_payment_id","razorpay_signature"},
 *         @OA\Property(property="razorpay_order_id", type="string", example="order_xxxxx"),
 *         @OA\Property(property="razorpay_payment_id", type="string", example="pay_xxxxx"),
 *         @OA\Property(property="razorpay_signature", type="string", example="signature_hex")
 *     )),
 *     @OA\Response(response=200, description="Payment verified, subscription activated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Invalid signature or order", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/subscriptions/change-plan",
 *     tags={"Subscriptions"},
 *     summary="Change subscription plan (may require Razorpay checkout)",
 *     operationId="changePlan",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"plan_id","billing_cycle"},
 *         @OA\Property(property="plan_id", type="integer"),
 *         @OA\Property(property="billing_cycle", type="string", enum={"monthly","yearly","lifetime"})
 *     )),
 *     @OA\Response(response=200, description="Plan changed or checkout required", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/subscriptions/cancel",
 *     tags={"Subscriptions"},
 *     summary="Cancel subscription",
 *     operationId="cancelSubscription",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Cancelled", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/subscriptions/invoices",
 *     tags={"Subscriptions"},
 *     summary="List subscription invoices",
 *     operationId="getSubscriptionInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=15)),
 *     @OA\Response(response=200, description="Paginated invoices", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/subscriptions/payments",
 *     tags={"Subscriptions"},
 *     summary="List subscription payments",
 *     operationId="getSubscriptionPayments",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=15)),
 *     @OA\Response(response=200, description="Paginated payments", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class SubscriptionDocs
{
    //
}
