<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/vouchers",
 *     tags={"Vouchers"},
 *     summary="Get all vouchers",
 *     description="Get paginated list of vouchers with optional filters. For payment, receipt, and adjustment modules use the dedicated /payments, /receipts, and /adjustments endpoints (same data, clearer Swagger grouping).",
 *     operationId="getVouchers",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", description="Search by voucher number or narration", @OA\Schema(type="string")),
 *     @OA\Parameter(name="voucher_type", in="query", description="Filter by voucher type", @OA\Schema(type="string", enum={"income", "expense", "receipt", "payment", "journal", "adjustment"})),
 *     @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="string", enum={"draft", "posted", "cancelled"})),
 *     @OA\Parameter(name="date_from", in="query", description="Filter from date", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="date_to", in="query", description="Filter to date", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="per_page", in="query", description="Items per page (default: 15)", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Voucher")),
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=5),
 *                 @OA\Property(property="per_page", type="integer", example=15),
 *                 @OA\Property(property="total", type="integer", example=75)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/vouchers/{id}",
 *     tags={"Vouchers"},
 *     summary="Get voucher by ID",
 *     description="Get single voucher details with lines",
 *     operationId="getVoucherById",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, description="Voucher ID", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Voucher")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/vouchers",
 *     tags={"Vouchers"},
 *     summary="Create new voucher",
 *     description="Prefer dedicated /payments, /receipts, /adjustments endpoints for those types (Tally-style rows). For payment/receipt this endpoint also accepts cash_bank_account_id + payment_rows (Cash/Bank/OD). For journal/adjustment accept adjustment_rows. Raw balanced lines still work for income/expense.",
 *     operationId="createVoucher",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Voucher data",
 *         @OA\JsonContent(
 *             required={"voucher_type", "voucher_date", "lines"},
 *             @OA\Property(property="voucher_type", type="string", enum={"income", "expense", "receipt", "payment", "journal", "adjustment"}, example="expense", description="Voucher type"),
 *             @OA\Property(property="voucher_date", type="string", format="date", example="2025-04-15", description="Voucher date"),
 *             @OA\Property(property="party_id", type="integer", example=1, description="Party ID (optional)"),
 *             @OA\Property(property="narration", type="string", example="Office supplies purchase", description="Narration"),
 *             @OA\Property(property="remarks", type="string", description="Remarks"),
 *             @OA\Property(property="lines", type="array", description="Voucher line items",
 *                 @OA\Items(
 *                     required={"account_id", "debit", "credit"},
 *                     @OA\Property(property="account_id", type="integer", example=1, description="Account ID"),
 *                     @OA\Property(property="debit", type="number", format="float", example=5000.00, description="Debit amount"),
 *                     @OA\Property(property="credit", type="number", format="float", example=0.00, description="Credit amount"),
 *                     @OA\Property(property="description", type="string", example="Office supplies", description="Line description")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Voucher created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Voucher created successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/Voucher")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/vouchers/{id}/post",
 *     tags={"Vouchers"},
 *     summary="Post voucher",
 *     description="Post a draft voucher (voucher must be balanced)",
 *     operationId="postVoucher",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, description="Voucher ID", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Voucher posted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Voucher posted successfully")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/vouchers/{id}",
 *     tags={"Vouchers"},
 *     summary="Update voucher",
 *     operationId="updateVoucher",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/vouchers/{id}",
 *     tags={"Vouchers"},
 *     summary="Delete voucher",
 *     operationId="deleteVoucher",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/vouchers/{id}/cancel",
 *     tags={"Vouchers"},
 *     summary="Cancel voucher",
 *     description="Cancel a posted voucher",
 *     operationId="cancelVoucher",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, description="Voucher ID", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Voucher cancelled successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Voucher cancelled successfully")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class VoucherDocs
{
    //
}
