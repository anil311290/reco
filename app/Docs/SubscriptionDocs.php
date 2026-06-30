<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
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
 *     summary="Subscribe to a plan",
 *     operationId="subscribe",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"plan_id","billing_cycle"},
 *         @OA\Property(property="plan_id", type="integer", example=2, description="Plan ID"),
 *         @OA\Property(property="billing_cycle", type="string", enum={"monthly","yearly"}, example="monthly")
 *     )),
 *     @OA\Response(response=201, description="Subscribed", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/subscriptions/change-plan",
 *     tags={"Subscriptions"},
 *     summary="Change subscription plan",
 *     operationId="changePlan",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"plan_id","billing_cycle"},
 *         @OA\Property(property="plan_id", type="integer"),
 *         @OA\Property(property="billing_cycle", type="string", enum={"monthly","yearly"})
 *     )),
 *     @OA\Response(response=200, description="Plan changed", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
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
 */
class SubscriptionDocs
{
    //
}
