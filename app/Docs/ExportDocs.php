<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/export/profit-loss/pdf",
 *     tags={"Export"},
 *     summary="Export profit and loss report as PDF",
 *     operationId="exportProfitLossPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/balance-sheet/pdf",
 *     tags={"Export"},
 *     summary="Export balance sheet report as PDF",
 *     operationId="exportBalanceSheetPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/trial-balance/pdf",
 *     tags={"Export"},
 *     summary="Export trial balance report as PDF",
 *     operationId="exportTrialBalancePdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/day-book/pdf",
 *     tags={"Export"},
 *     summary="Export day book report as PDF",
 *     operationId="exportDayBookPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="date", in="query", required=true, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/ledger/pdf",
 *     tags={"Export"},
 *     summary="Export ledger report as PDF",
 *     operationId="exportLedgerPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="account_id", in="query", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/debtors-outstanding/pdf",
 *     tags={"Export"},
 *     summary="Export debtors outstanding report as PDF",
 *     operationId="exportDebtorsOutstandingPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/creditors-outstanding/pdf",
 *     tags={"Export"},
 *     summary="Export creditors outstanding report as PDF",
 *     operationId="exportCreditorsOutstandingPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/voucher/{id}/pdf",
 *     tags={"Export"},
 *     summary="Export voucher as PDF",
 *     operationId="exportVoucherPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/sales-invoice/{id}/pdf",
 *     tags={"Export"},
 *     summary="Export sales invoice as PDF",
 *     operationId="exportSalesInvoicePdfByExportRoute",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class ExportDocs
{
    //
}
