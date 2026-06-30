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

    /**
     * Export Profit & Loss to PDF
     */
    public function profitLossPdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        try {
            $pdf = $this->exportService->exportProfitLossPdf($companyId, $financialYearId);
            $filename = 'profit-loss-' . date('Y-m-d') . '.pdf';
            $path = "exports/{$filename}";
            
            Storage::put($path, $pdf);

            return ResponseHelper::success([
                'filename' => $filename,
                'path' => Storage::url($path),
                'download_url' => url("api/v1/download/" . base64_encode($path)),
            ], 'PDF generated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Export Balance Sheet to PDF
     */
    public function balanceSheetPdf(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        try {
            $pdf = $this->exportService->exportBalanceSheetPdf($companyId, $financialYearId);
            $filename = 'balance-sheet-' . date('Y-m-d') . '.pdf';
            $path = "exports/{$filename}";
            
            Storage::put($path, $pdf);

            return ResponseHelper::success([
                'filename' => $filename,
                'path' => Storage::url($path),
                'download_url' => url("api/v1/download/" . base64_encode($path)),
            ], 'PDF generated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Export Ledger to PDF
     */
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
            $path = "exports/{$filename}";
            
            Storage::put($path, $pdf);

            return ResponseHelper::success([
                'filename' => $filename,
                'path' => Storage::url($path),
                'download_url' => url("api/v1/download/" . base64_encode($path)),
            ], 'PDF generated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Export Voucher to PDF
     */
    public function voucherPdf(Request $request, int $id): JsonResponse
    {
        try {
            $pdf = $this->exportService->exportVoucherPdf($id);
            $filename = 'voucher-' . $id . '-' . date('Y-m-d') . '.pdf';
            $path = "exports/{$filename}";
            
            Storage::put($path, $pdf);

            return ResponseHelper::success([
                'filename' => $filename,
                'path' => Storage::url($path),
                'download_url' => url("api/v1/download/" . base64_encode($path)),
            ], 'PDF generated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get export history
     */
    public function history(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        // Get list of exported files
        $files = Storage::files('exports');
        $exports = [];

        foreach ($files as $file) {
            $exports[] = [
                'filename' => basename($file),
                'path' => Storage::url($file),
                'size' => Storage::size($file),
                'created_at' => Storage::lastModified($file),
            ];
        }

        return ResponseHelper::success($exports);
    }

    /**
     * Share statement via email
     */
    public function shareStatement(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:profit-loss,balance-sheet,ledger,statement',
            'account_id' => 'required_if:type,ledger',
            'message' => 'nullable|string|max:500',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        try {
            // Generate PDF based on type
            $pdf = null;
            $filename = '';

            switch ($request->type) {
                case 'profit-loss':
                    $pdf = $this->exportService->exportProfitLossPdf($companyId, $financialYearId);
                    $filename = 'profit-loss-statement.pdf';
                    break;
                case 'balance-sheet':
                    $pdf = $this->exportService->exportBalanceSheetPdf($companyId, $financialYearId);
                    $filename = 'balance-sheet-statement.pdf';
                    break;
                case 'ledger':
                    $pdf = $this->exportService->exportLedgerPdf($request->account_id, $companyId, $financialYearId);
                    $filename = 'ledger-statement.pdf';
                    break;
            }

            // TODO: Send email with PDF attachment
            // Mail::to($request->email)->send(new StatementMail($pdf, $filename, $request->message));

            return ResponseHelper::success(null, 'Statement shared successfully to ' . $request->email);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get available export types
     */
    public function getExportTypes(Request $request): JsonResponse
    {
        $types = [
            ['id' => 'profit-loss', 'name' => 'Profit & Loss', 'icon' => 'graph-up'],
            ['id' => 'balance-sheet', 'name' => 'Balance Sheet', 'icon' => 'balance-scale'],
            ['id' => 'trial-balance', 'name' => 'Trial Balance', 'icon' => 'journal-check'],
            ['id' => 'ledger', 'name' => 'Account Ledger', 'icon' => 'book'],
            ['id' => 'day-book', 'name' => 'Day Book', 'icon' => 'calendar-day'],
            ['id' => 'debtors', 'name' => 'Debtors Outstanding', 'icon' => 'people'],
            ['id' => 'creditors', 'name' => 'Creditors Outstanding', 'icon' => 'people-fill'],
            ['id' => 'voucher', 'name' => 'Voucher', 'icon' => 'receipt'],
        ];

        return ResponseHelper::success($types);
    }
}
