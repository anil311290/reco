<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/export/types",
 *     tags={"Export"},
 *     summary="Get export types",
 *     operationId="getExportTypes",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Export types fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/profit-loss/pdf",
 *     tags={"Export"},
 *     summary="Export profit and loss report as PDF",
 *     operationId="exportProfitLossPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="PDF generated")
 * )
 *
 * @OA\Get(
 *     path="/export/balance-sheet/pdf",
 *     tags={"Export"},
 *     summary="Export balance sheet report as PDF",
 *     operationId="exportBalanceSheetPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="PDF generated")
 * )
 *
 * @OA\Get(
 *     path="/export/ledger/pdf",
 *     tags={"Export"},
 *     summary="Export ledger report as PDF",
 *     operationId="exportLedgerPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="account_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated")
 * )
 *
 * @OA\Get(
 *     path="/export/voucher/{id}/pdf",
 *     tags={"Export"},
 *     summary="Export voucher as PDF",
 *     operationId="exportVoucherPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated"),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/history",
 *     tags={"Export"},
 *     summary="Get export history",
 *     operationId="getExportHistory",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
 *     @OA\Response(response=200, description="Export history fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/export/share",
 *     tags={"Export"},
 *     summary="Share exported statement",
 *     operationId="shareExportStatement",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"type", "recipient"},
 *             @OA\Property(property="type", type="string", example="ledger_pdf"),
 *             @OA\Property(property="recipient", type="string", example="client@example.com"),
 *             @OA\Property(property="message", type="string", example="Please find attached statement")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Statement shared", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class ExportDocs
{
    //
}
