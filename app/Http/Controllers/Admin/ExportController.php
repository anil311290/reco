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
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$financialYearId) {
            return back()->with('error', 'No active financial year found');
        }

        $pdf = $this->exportService->exportProfitLossPdf($companyId, (int) $financialYearId, $dateFrom, $dateTo);

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
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$financialYearId) {
            return back()->with('error', 'No active financial year found');
        }

        $pdf = $this->exportService->exportBalanceSheetPdf($companyId, (int) $financialYearId, $dateFrom, $dateTo);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="balance-sheet-report.pdf"');
    }

    /**
     * Export Trial Balance to PDF
     */
    public function trialBalancePdf(Request $request): Response|RedirectResponse
    {
        $companyId = $this->getCompanyId($request);
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$financialYearId) {
            return back()->with('error', 'No active financial year found');
        }

        $pdf = $this->exportService->exportTrialBalancePdf($companyId, (int) $financialYearId, $dateFrom, $dateTo);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="trial-balance-report.pdf"');
    }

    /**
     * Export Receipt & Payment to PDF
     */
    public function receiptPaymentPdf(Request $request): Response
    {
        $companyId = $this->getCompanyId($request);

        $pdf = $this->exportService->exportReceiptPaymentPdf(
            $companyId,
            $request->input('date_from'),
            $request->input('date_to'),
            $request->filled('financial_year_id') ? (int) $request->input('financial_year_id') : null
        );

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="receipt-payment-report.pdf"');
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
        $filters = $request->only(['overdue_status', 'age_bucket']);

        $pdf = $this->exportService->exportDebtorsOutstandingPdf(
            $companyId,
            $request->filled('financial_year_id') ? (int) $request->input('financial_year_id') : null,
            $request->input('date_from'),
            $request->input('date_to'),
            $filters
        );

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
        $filters = $request->only(['overdue_status', 'age_bucket']);

        $pdf = $this->exportService->exportCreditorsOutstandingPdf(
            $companyId,
            $request->filled('financial_year_id') ? (int) $request->input('financial_year_id') : null,
            $request->input('date_from'),
            $request->input('date_to'),
            $filters
        );

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="creditors-outstanding-report.pdf"');
    }

    /**
     * Export Aging Summary to PDF
     */
    public function agingSummaryPdf(Request $request): Response
    {
        $companyId = $this->getCompanyId($request);
        $filters = $request->only(['overdue_status', 'age_bucket']);

        $pdf = $this->exportService->exportAgingSummaryPdf(
            $companyId,
            $request->filled('financial_year_id') ? (int) $request->input('financial_year_id') : null,
            $request->input('date_from'),
            $request->input('date_to'),
            $filters
        );

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="aging-summary-report.pdf"');
    }

    /**
     * Export Day Book to PDF
     */
    public function dayBookPdf(Request $request): Response
    {
        $companyId = $this->getCompanyId($request);
        $dateFrom = $request->input('date_from', $request->input('date', date('Y-m-d')));
        $dateTo = $request->input('date_to', $dateFrom);

        $pdf = $this->exportService->exportDayBookPdf(
            $companyId,
            $dateFrom,
            $request->filled('financial_year_id') ? (int) $request->input('financial_year_id') : null,
            $dateTo
        );

        $suffix = $dateFrom === $dateTo
            ? $dateFrom
            : ($dateFrom . '-to-' . $dateTo);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="day-book-' . $suffix . '.pdf"');
    }

    /**
     * Export to Excel
     */
    public function excel(Request $request, string $type): Response
    {
        $companyId = $this->getCompanyId($request);
        $filters = $request->query();

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
        $filters = $request->query();

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
