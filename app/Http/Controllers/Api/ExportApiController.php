<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use App\Services\ReportService;
use App\Models\FinancialYear;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportApiController extends Controller
{
    protected ExportService $exportService;
    protected ReportService $reportService;

    public function __construct(ExportService $exportService, ReportService $reportService)
    {
        $this->exportService = $exportService;
        $this->reportService = $reportService;
    }

    public function profitLossPdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        try {
            $pdf = $this->exportService->exportProfitLossPdf($companyId, $financialYearId);

            return $this->storePdfResponse($pdf, 'profit-loss-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function balanceSheetPdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        try {
            $pdf = $this->exportService->exportBalanceSheetPdf($companyId, $financialYearId);

            return $this->storePdfResponse($pdf, 'balance-sheet-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function profitLossExcel(Request $request): JsonResponse
    {
        return $this->reportExcelResponse($request, 'profit-loss');
    }

    public function balanceSheetExcel(Request $request): JsonResponse
    {
        return $this->reportExcelResponse($request, 'balance-sheet');
    }

    public function trialBalancePdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        try {
            $pdf = $this->exportService->exportTrialBalancePdf($companyId, $financialYearId);

            return $this->storePdfResponse($pdf, 'trial-balance-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function trialBalanceExcel(Request $request): JsonResponse
    {
        return $this->reportExcelResponse($request, 'trial-balance');
    }

    public function dayBookPdf(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $companyId = $request->user()->company_id;

        try {
            $pdf = $this->exportService->exportDayBookPdf(
                $companyId,
                $request->date,
                $request->filled('financial_year_id') ? (int) $request->financial_year_id : null
            );

            return $this->storePdfResponse($pdf, 'day-book-' . $request->date . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function dayBookExcel(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        return $this->reportExcelResponse($request, 'day-book');
    }

    public function cashBookPdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $pdf = $this->exportService->exportCashBookPdf(
                $companyId,
                $request->filled('account_id') ? (int) $request->account_id : null,
                $request->input('date_from'),
                $request->input('date_to'),
                $request->filled('financial_year_id') ? (int) $request->financial_year_id : null
            );

            return $this->storePdfResponse($pdf, 'cash-book-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function cashBookExcel(Request $request): JsonResponse
    {
        return $this->reportExcelResponse($request, 'cash-book');
    }

    public function bankBookPdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $pdf = $this->exportService->exportBankBookPdf(
                $companyId,
                $request->filled('account_id') ? (int) $request->account_id : null,
                $request->input('date_from'),
                $request->input('date_to'),
                $request->filled('financial_year_id') ? (int) $request->financial_year_id : null
            );

            return $this->storePdfResponse($pdf, 'bank-book-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function bankBookExcel(Request $request): JsonResponse
    {
        return $this->reportExcelResponse($request, 'bank-book');
    }

    public function ledgerPdf(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        try {
            $pdf = $this->exportService->exportLedgerPdf($request->account_id, $companyId, $financialYearId);
            $filename = 'ledger-' . $request->account_id . '-' . date('Y-m-d') . '.pdf';

            return $this->storePdfResponse($pdf, $filename);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function ledgerExcel(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        return $this->reportExcelResponse($request, 'ledger');
    }

    public function debtorsOutstandingPdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $pdf = $this->exportService->exportDebtorsOutstandingPdf($companyId);

            return $this->storePdfResponse($pdf, 'debtors-outstanding-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function debtorsOutstandingExcel(Request $request): JsonResponse
    {
        return $this->reportExcelResponse($request, 'debtors');
    }

    public function creditorsOutstandingPdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $pdf = $this->exportService->exportCreditorsOutstandingPdf($companyId);

            return $this->storePdfResponse($pdf, 'creditors-outstanding-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function creditorsOutstandingExcel(Request $request): JsonResponse
    {
        return $this->reportExcelResponse($request, 'creditors');
    }

    public function voucherPdf(Request $request, int $id): JsonResponse
    {
        try {
            $pdf = $this->exportService->exportVoucherPdf($id);
            $filename = 'voucher-' . $id . '-' . date('Y-m-d') . '.pdf';

            return $this->storePdfResponse($pdf, $filename);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function salesInvoicePdf(int $id): JsonResponse
    {
        try {
            $pdf = $this->exportService->exportSalesInvoicePdf($id);
            $filename = 'sales-invoice-' . $id . '-' . date('Y-m-d') . '.pdf';

            return $this->storePdfResponse($pdf, $filename);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function masterExcel(Request $request, string $type): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $excel = $this->exportService->exportToExcel(
                $this->resolveMasterType($type),
                $companyId,
                $request->all()
            );

            return $this->storeBinaryResponse(
                $excel,
                $type . '-' . date('Y-m-d-H-i-s') . '.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function masterPdf(Request $request, string $type): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $pdf = $this->exportService->exportMasterPdf(
                $this->resolveMasterType($type),
                $companyId,
                $request->all()
            );

            return $this->storeBinaryResponse(
                $pdf,
                $type . '-' . date('Y-m-d-H-i-s') . '.pdf',
                'application/pdf'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    protected function storePdfResponse(string $pdf, string $filename): JsonResponse
    {
        return $this->storeBinaryResponse($pdf, $filename, 'application/pdf');
    }

    protected function reportExcelResponse(Request $request, string $type): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $excel = $this->exportService->exportToExcel($type, $companyId, $request->all());

            return $this->storeBinaryResponse(
                $excel,
                $type . '-' . date('Y-m-d-H-i-s') . '.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    protected function storeBinaryResponse(string $content, string $filename, string $contentType = 'application/pdf'): JsonResponse
    {
        $path = "exports/{$filename}";
        Storage::put($path, $content);

        return ResponseHelper::success([
            'filename' => $filename,
            'content_type' => $contentType,
            'content_base64' => base64_encode($content),
            'path' => Storage::url($path),
        ], 'Export generated successfully');
    }

    protected function resolveMasterType(string $type): string
    {
        $allowed = ['accounts', 'parties', 'items', 'item-categories', 'tax-rates'];

        if (!in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported export type');
        }

        return $type;
    }
}
