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
 *     path="/export/receipt-payment/pdf",
 *     tags={"Export"},
 *     summary="Export receipt and payment report as PDF",
 *     operationId="exportReceiptPaymentPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="financial_year_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/receipt-payment/excel",
 *     tags={"Export"},
 *     summary="Export receipt and payment report as Excel",
 *     operationId="exportReceiptPaymentExcel",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="financial_year_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Excel generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
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
 *
 * @OA\Get(
 *     path="/export/masters/{type}/excel",
 *     tags={"Export"},
 *     summary="Export a master list as Excel",
 *     operationId="exportMasterExcel",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="type",
 *         in="path",
 *         required=true,
 *         description="Supported values: accounts, parties, items, item-categories, tax-rates",
 *         @OA\Schema(type="string", enum={"accounts","parties","items","item-categories","tax-rates"})
 *     ),
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="integer", enum={0,1})),
 *     @OA\Parameter(name="account_type", in="query", description="Accounts only", @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", description="Parties/items only", @OA\Schema(type="string")),
 *     @OA\Parameter(name="category_id", in="query", description="Items only", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="tax_category", in="query", description="Tax rates only", @OA\Schema(type="string")),
 *     @OA\Parameter(name="tax_type", in="query", description="Tax rates only", @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", description="Tax rates only", @OA\Schema(type="string", enum={"active","inactive"})),
 *     @OA\Response(response=200, description="Excel generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/export/masters/{type}/pdf",
 *     tags={"Export"},
 *     summary="Export a master list as PDF",
 *     operationId="exportMasterPdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="type",
 *         in="path",
 *         required=true,
 *         description="Supported values: accounts, parties, items, item-categories, tax-rates",
 *         @OA\Schema(type="string", enum={"accounts","parties","items","item-categories","tax-rates"})
 *     ),
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="integer", enum={0,1})),
 *     @OA\Parameter(name="account_type", in="query", description="Accounts only", @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", description="Parties/items only", @OA\Schema(type="string")),
 *     @OA\Parameter(name="category_id", in="query", description="Items only", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="tax_category", in="query", description="Tax rates only", @OA\Schema(type="string")),
 *     @OA\Parameter(name="tax_type", in="query", description="Tax rates only", @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", description="Tax rates only", @OA\Schema(type="string", enum={"active","inactive"})),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class ExportDocs
{
    //
}
