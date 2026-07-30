<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment vouchers (money out). Same Tally-style payload as web admin: cash_bank_account_id + payment_rows. Paid From may be any Cash, Bank, or OD ledger. Server builds balanced ledger lines and auto-posts."
 * )
 *
 * @OA\Get(
 *     path="/payments",
 *     tags={"Payments"},
 *     summary="List payment vouchers",
 *     operationId="getPayments",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft", "posted", "cancelled"})),
 *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/payments/{id}",
 *     tags={"Payments"},
 *     summary="Get payment voucher",
 *     operationId="getPaymentById",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="data", ref="#/components/schemas/Voucher")
 *     )),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/payments",
 *     tags={"Payments"},
 *     summary="Create payment voucher",
 *     description="Tally-style payment (same as web). Debit particulars (creditors), credit Cash/Bank/OD. Use GET /accounts/cash-bank and GET /accounts/payment-particulars?type=payment for form options.",
 *     operationId="createPayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"voucher_date","cash_bank_account_id","payment_rows"},
 *         @OA\Property(property="voucher_date", type="string", format="date", example="2026-07-16"),
 *         @OA\Property(property="cash_bank_account_id", type="integer", example=12, description="Paid From Cash/Bank/OD account"),
 *         @OA\Property(property="party_id", type="integer", nullable=true, description="Optional; auto-resolved from party particulars"),
 *         @OA\Property(property="narration", type="string", example="Supplier payment"),
 *         @OA\Property(property="remarks", type="string"),
 *         @OA\Property(property="payment_rows", type="array", minItems=1, @OA\Items(
 *             required={"account_id","amount"},
 *             @OA\Property(property="account_id", type="integer", example=45, description="Particulars account (party ledger)"),
 *             @OA\Property(property="amount", type="number", format="float", example=1000.00),
 *             @OA\Property(property="description", type="string", example="Bill settlement")
 *         ))
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/payments/{id}",
 *     tags={"Payments"},
 *     summary="Update payment voucher",
 *     description="Same body shape as create.",
 *     operationId="updatePayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"voucher_date","cash_bank_account_id","payment_rows"},
 *         @OA\Property(property="voucher_date", type="string", format="date"),
 *         @OA\Property(property="cash_bank_account_id", type="integer", description="Paid From Cash/Bank/OD account"),
 *         @OA\Property(property="narration", type="string"),
 *         @OA\Property(property="remarks", type="string"),
 *         @OA\Property(property="payment_rows", type="array", @OA\Items(
 *             @OA\Property(property="account_id", type="integer"),
 *             @OA\Property(property="amount", type="number"),
 *             @OA\Property(property="description", type="string")
 *         ))
 *     )),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/payments/{id}",
 *     tags={"Payments"},
 *     summary="Delete payment voucher",
 *     operationId="deletePayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/payments/{id}/cancel",
 *     tags={"Payments"},
 *     summary="Cancel payment voucher",
 *     operationId="cancelPayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Cancelled", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class PaymentDocs
{
    //
}
