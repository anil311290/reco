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

    public function dayBookPdf(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $companyId = $request->user()->company_id;

        try {
            $pdf = $this->exportService->exportDayBookPdf($companyId, $request->date);

            return $this->storePdfResponse($pdf, 'day-book-' . $request->date . '.pdf');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
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

    protected function storePdfResponse(string $pdf, string $filename): JsonResponse
    {
        $path = "exports/{$filename}";
        Storage::put($path, $pdf);

        return ResponseHelper::success([
            'filename' => $filename,
            'path' => Storage::url($path),
            'download_url' => url('api/v1/download/' . base64_encode($path)),
        ], 'PDF generated successfully');
    }
}
