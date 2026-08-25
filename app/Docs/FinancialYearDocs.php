<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/financial-years",
 *     tags={"Financial Years"},
 *     summary="List financial years",
 *     operationId="getFinancialYearsList",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/financial-years/current",
 *     tags={"Financial Years"},
 *     summary="Get current financial year",
 *     operationId="getFinancialYearCurrent",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/financial-years",
 *     tags={"Financial Years"},
 *     summary="Create financial year",
 *     operationId="createFinancialYear",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"name","start_date","end_date"},
 *         @OA\Property(property="name", type="string", example="2025-26"),
 *         @OA\Property(property="start_date", type="string", format="date"),
 *         @OA\Property(property="end_date", type="string", format="date")
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/financial-years/{id}/set-current",
 *     tags={"Financial Years"},
 *     summary="Set financial year as current",
 *     description="Marks this FY as current and carries forward closing ledger balances into the new FY as opening ledger entries (same as web).",
 *     operationId="setFinancialYearCurrent",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/financial-years/{id}/close",
 *     tags={"Financial Years"},
 *     summary="Close financial year",
 *     operationId="closeFinancialYear",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Closed", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/financial-years/{id}",
 *     tags={"Financial Years"},
 *     summary="Delete financial year",
 *     operationId="deleteFinancialYear",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/financial-years/{id}/restore",
 *     tags={"Settings"},
 *     summary="Restore a soft-deleted financial year",
 *     operationId="restoreFinancialYear",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=2)),
 *     @OA\Response(
 *         response=200,
 *         description="Financial year restored",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Financial year restored successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=400, description="Not found in trash", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class FinancialYearDocs
{
    //
}
