<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Adjustments",
 *     description="Adjustment / journal vouchers. Same Tally-style payload as web: adjustment_rows with entry_type debit|credit. Server builds balanced lines and auto-posts. Use GET /accounts/adjustment-particulars for options."
 * )
 *
 * @OA\Get(
 *     path="/adjustments",
 *     tags={"Adjustments"},
 *     summary="List adjustment vouchers",
 *     operationId="getAdjustments",
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
 *     path="/adjustments/{id}",
 *     tags={"Adjustments"},
 *     summary="Get adjustment voucher",
 *     operationId="getAdjustmentById",
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
 *     path="/adjustments",
 *     tags={"Adjustments"},
 *     summary="Create adjustment voucher",
 *     description="Tally journal style (same as web). At least two lines with debit and credit that balance. voucher_type is forced to journal.",
 *     operationId="createAdjustment",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"voucher_date","adjustment_rows"},
 *         @OA\Property(property="voucher_date", type="string", format="date", example="2026-07-16"),
 *         @OA\Property(property="narration", type="string", example="Opening balance adjustment"),
 *         @OA\Property(property="remarks", type="string"),
 *         @OA\Property(property="adjustment_rows", type="array", minItems=2, @OA\Items(
 *             required={"account_id","entry_type","amount"},
 *             @OA\Property(property="account_id", type="integer", example=12),
 *             @OA\Property(property="entry_type", type="string", enum={"debit","credit"}, example="debit"),
 *             @OA\Property(property="amount", type="number", format="float", example=2500.00),
 *             @OA\Property(property="description", type="string", example="Debit adjustment")
 *         ))
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/adjustments/{id}",
 *     tags={"Adjustments"},
 *     summary="Update adjustment voucher",
 *     description="Same body shape as create.",
 *     operationId="updateAdjustment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"voucher_date","adjustment_rows"},
 *         @OA\Property(property="voucher_date", type="string", format="date"),
 *         @OA\Property(property="narration", type="string"),
 *         @OA\Property(property="remarks", type="string"),
 *         @OA\Property(property="adjustment_rows", type="array", minItems=2, @OA\Items(
 *             @OA\Property(property="account_id", type="integer"),
 *             @OA\Property(property="entry_type", type="string", enum={"debit","credit"}),
 *             @OA\Property(property="amount", type="number"),
 *             @OA\Property(property="description", type="string")
 *         ))
 *     )),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/adjustments/{id}",
 *     tags={"Adjustments"},
 *     summary="Delete adjustment voucher",
 *     operationId="deleteAdjustment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/adjustments/{id}/cancel",
 *     tags={"Adjustments"},
 *     summary="Cancel adjustment voucher",
 *     operationId="cancelAdjustment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Cancelled", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class AdjustmentDocs
{
    //
}
