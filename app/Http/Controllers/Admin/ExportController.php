<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use App\Models\FinancialYear;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    protected ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export Profit & Loss to PDF
     */
    public function profitLossPdf(Request $request): Response|RedirectResponse
    {
        $companyId = $this->getCompanyId($request);
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return back()->with('error', 'No active financial year found');
        }

        $pdf = $this->exportService->exportProfitLossPdf($companyId, $financialYearId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="profit-loss-report.pdf"');
    }

    /**
     * Export Balance Sheet to PDF
     */
    public function balanceSheetPdf(Request $request): Response|RedirectResponse
    {
        $companyId = $this->getCompanyId($request);
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return back()->with('error', 'No active financial year found');
        }

        $pdf = $this->exportService->exportBalanceSheetPdf($companyId, $financialYearId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="balance-sheet-report.pdf"');
    }

    /**
     * Export Cash Flow to PDF
     */
    public function cashFlowPdf(Request $request): Response|RedirectResponse
    {
        $companyId = $this->getCompanyId($request);
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return back()->with('error', 'No active financial year found');
        }

        $pdf = $this->exportService->exportCashFlowPdf($companyId, $financialYearId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="cash-flow-report.pdf"');
    }

    /**
     * Export Trial Balance to PDF
     */
    public function trialBalancePdf(Request $request): Response|RedirectResponse
    {
        $companyId = $this->getCompanyId($request);
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return back()->with('error', 'No active financial year found');
        }

        $pdf = $this->exportService->exportTrialBalancePdf($companyId, $financialYearId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="trial-balance-report.pdf"');
    }

    /**
     * Export Ledger to PDF
     */
    public function ledgerPdf(Request $request): Response|RedirectResponse
    {
        $companyId = $this->getCompanyId($request);
        $accountId = $request->input('account_id');
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$accountId) {
            return back()->with('error', 'Please select an account');
        }

        $pdf = $this->exportService->exportLedgerPdf((int) $accountId, $companyId, $financialYearId, $dateFrom, $dateTo);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="ledger-report.pdf"');
    }

    /**
     * Export Voucher to PDF
     */
    public function voucherPdf(int $id): Response
    {
        $pdf = $this->exportService->exportVoucherPdf($id);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="voucher-' . $id . '.pdf"');
    }

    /**
     * Export Debtors Outstanding to PDF
     */
    public function debtorsOutstandingPdf(Request $request): Response
    {
        $companyId = $this->getCompanyId($request);

        $pdf = $this->exportService->exportDebtorsOutstandingPdf($companyId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="debtors-outstanding-report.pdf"');
    }

    /**
     * Export Creditors Outstanding to PDF
     */
    public function creditorsOutstandingPdf(Request $request): Response
    {
        $companyId = $this->getCompanyId($request);

        $pdf = $this->exportService->exportCreditorsOutstandingPdf($companyId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="creditors-outstanding-report.pdf"');
    }

    /**
     * Export Day Book to PDF
     */
    public function dayBookPdf(Request $request): Response
    {
        $companyId = $this->getCompanyId($request);
        $date = $request->input('date', date('Y-m-d'));

        $pdf = $this->exportService->exportDayBookPdf($companyId, $date);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="day-book-' . $date . '.pdf"');
    }

    /**
     * Export to Excel
     */
    public function excel(Request $request, string $type): Response
    {
        $companyId = $this->getCompanyId($request);
        $filters = $request->only(['type', 'date', 'date_from', 'date_to', 'voucher_type', 'financial_year_id', 'account_id']);

        $excel = $this->exportService->exportToExcel($type, $companyId, $filters);

        return response($excel)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="' . $type . '-export.xlsx"');
    }

    /**
     * Export to CSV
     */
    public function csv(Request $request, string $type): Response
    {
        $companyId = $this->getCompanyId($request);
        $filters = $request->only(['type', 'date', 'date_from', 'date_to', 'voucher_type', 'financial_year_id', 'account_id']);

        $csv = $this->exportService->exportToCsv($type, $companyId, $filters);

        return response("\xEF\xBB\xBF" . $csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $type . '-export.csv"');
    }

    private function getCompanyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }
}
