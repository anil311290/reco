<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Receipts",
 *     description="Receipt vouchers (money in). Same Tally-style payload as web admin: payment_mode + cash_bank_account_id + payment_rows. Server builds balanced ledger lines and auto-posts."
 * )
 *
 * @OA\Get(
 *     path="/receipts",
 *     tags={"Receipts"},
 *     summary="List receipt vouchers",
 *     operationId="getReceipts",
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
 *     path="/receipts/{id}",
 *     tags={"Receipts"},
 *     summary="Get receipt voucher",
 *     operationId="getReceiptById",
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
 *     path="/receipts",
 *     tags={"Receipts"},
 *     summary="Create receipt voucher",
 *     description="Tally-style receipt (same as web). Debit Cash/Bank/OD, credit particulars (debtors). Use GET /accounts/cash-bank and GET /accounts/payment-particulars?type=receipt for form options.",
 *     operationId="createReceipt",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"voucher_date","payment_mode","cash_bank_account_id","payment_rows"},
 *         @OA\Property(property="voucher_date", type="string", format="date", example="2026-07-16"),
 *         @OA\Property(property="payment_mode", type="string", enum={"cash","bank","od"}, example="bank"),
 *         @OA\Property(property="cash_bank_account_id", type="integer", example=12, description="Received In account (must match payment_mode)"),
 *         @OA\Property(property="party_id", type="integer", nullable=true),
 *         @OA\Property(property="narration", type="string", example="Customer receipt"),
 *         @OA\Property(property="remarks", type="string"),
 *         @OA\Property(property="payment_rows", type="array", minItems=1, @OA\Items(
 *             required={"account_id","amount"},
 *             @OA\Property(property="account_id", type="integer", example=45, description="Particulars account (party ledger)"),
 *             @OA\Property(property="amount", type="number", format="float", example=2500.00),
 *             @OA\Property(property="description", type="string", example="Against invoice")
 *         ))
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/receipts/{id}",
 *     tags={"Receipts"},
 *     summary="Update receipt voucher",
 *     description="Same body shape as create.",
 *     operationId="updateReceipt",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"voucher_date","payment_mode","cash_bank_account_id","payment_rows"},
 *         @OA\Property(property="voucher_date", type="string", format="date"),
 *         @OA\Property(property="payment_mode", type="string", enum={"cash","bank","od"}),
 *         @OA\Property(property="cash_bank_account_id", type="integer"),
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
 *     path="/receipts/{id}",
 *     tags={"Receipts"},
 *     summary="Delete receipt voucher",
 *     operationId="deleteReceipt",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/receipts/{id}/cancel",
 *     tags={"Receipts"},
 *     summary="Cancel receipt voucher",
 *     operationId="cancelReceipt",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Cancelled", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class ReceiptDocs
{
    //
}
