<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\SalesInvoice;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Party;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;

class ExportService
{
    protected ReportService $reportService;
    protected LedgerService $ledgerService;

    public function __construct(ReportService $reportService, LedgerService $ledgerService)
    {
        $this->reportService = $reportService;
        $this->ledgerService = $ledgerService;
    }

    /**
     * Export Profit & Loss to PDF
     */
    public function exportProfitLossPdf(int $companyId, int $financialYearId): string
    {
        $report = $this->reportService->getProfitLoss($companyId, $financialYearId);

        $pdf = Pdf::loadView('exports.profit-loss', compact('report'));

        return $pdf->output();
    }

    /**
     * Export Balance Sheet to PDF
     */
    public function exportBalanceSheetPdf(int $companyId, int $financialYearId): string
    {
        $report = $this->reportService->getBalanceSheet($companyId, $financialYearId);

        $pdf = Pdf::loadView('exports.balance-sheet', compact('report'));

        return $pdf->output();
    }

    /**
     * Export Trial Balance to PDF
     */
    public function exportTrialBalancePdf(int $companyId, int $financialYearId): string
    {
        $report = $this->ledgerService->getTrialBalance($companyId, $financialYearId);

        $pdf = Pdf::loadView('exports.trial-balance', compact('report'));

        return $pdf->output();
    }

    /**
     * Export Ledger to PDF
     */
    public function exportLedgerPdf(
        int $accountId,
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): string
    {
        $report = $this->ledgerService->getAccountLedger($accountId, $companyId, $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.ledger', compact('report'));

        return $pdf->output();
    }

    /**
     * Export Voucher to PDF
     */
    public function exportVoucherPdf(int $voucherId): string
    {
        $voucher = Voucher::with(['party', 'lines.account', 'company'])
            ->where('id', $voucherId)
            ->orWhere('sales_invoice_id', $voucherId)
            ->orWhere('purchase_invoice_id', $voucherId)
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$voucherId])
            ->firstOrFail();

        $pdf = Pdf::loadView('exports.voucher', compact('voucher'));

        return $pdf->output();
    }

    /**
     * Export Sales Invoice to PDF
     */
    public function exportSalesInvoicePdf(int $invoiceId): string
    {
        $invoice = SalesInvoice::with([
            'company',
            'party',
            'lines.item',
            'lines.taxRate',
            'lines.account',
            'financialYear',
        ])->findOrFail($invoiceId);

        $pdf = Pdf::loadView('exports.sales-invoice', compact('invoice'));

        return $pdf->output();
    }

    /**
     * Export Debtors Outstanding to PDF
     */
    public function exportDebtorsOutstandingPdf(int $companyId): string
    {
        $report = $this->reportService->getDebtorsOutstanding($companyId);

        $pdf = Pdf::loadView('exports.debtors-outstanding', compact('report'));

        return $pdf->output();
    }

    /**
     * Export Creditors Outstanding to PDF
     */
    public function exportCreditorsOutstandingPdf(int $companyId): string
    {
        $report = $this->reportService->getCreditorsOutstanding($companyId);

        $pdf = Pdf::loadView('exports.creditors-outstanding', compact('report'));

        return $pdf->output();
    }

    /**
     * Export Day Book to PDF
     */
    public function exportDayBookPdf(int $companyId, string $date, ?int $financialYearId = null): string
    {
        $financialYearId = $financialYearId ?? \App\Models\FinancialYear::getCurrent($companyId)?->id;
        $report = $this->reportService->getDayBook($companyId, $date, $financialYearId);

        $pdf = Pdf::loadView('exports.day-book', compact('report', 'date'));

        return $pdf->output();
    }

    /**
     * Export data to Excel
     */
    public function exportToExcel(string $type, int $companyId, array $filters = []): string
    {
        $data = [];

        switch ($type) {
            case 'accounts':
                $data = Account::where('company_id', $companyId)
                    ->orderBy('account_code')
                    ->get()
                    ->toArray();
                break;

            case 'parties':
                $query = Party::where('company_id', $companyId);
                if (isset($filters['type'])) {
                    $query->where('type', $filters['type']);
                }
                $data = $query->orderBy('name')->get()->toArray();
                break;

            case 'vouchers':
                $query = Voucher::where('company_id', $companyId)
                    ->where('status', 'posted');
                if (isset($filters['voucher_type'])) {
                    $query->where('voucher_type', $filters['voucher_type']);
                }
                if (isset($filters['date_from'])) {
                    $query->where('voucher_date', '>=', $filters['date_from']);
                }
                if (isset($filters['date_to'])) {
                    $query->where('voucher_date', '<=', $filters['date_to']);
                }
                $data = $query->orderBy('voucher_date', 'desc')->get()->toArray();
                break;

            case 'debtors':
                $data = collect($this->reportService->getDebtorsOutstanding($companyId)['debtors'])
                    ->map(function ($item) {
                        return [
                            'party' => $item['party']->name ?? '-',
                            'mobile' => $item['party']->mobile ?? '-',
                            'email' => $item['party']->email ?? '-',
                            'debit' => $item['debit'] ?? 0,
                            'credit' => $item['credit'] ?? 0,
                            'balance' => $item['balance'] ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
                break;

            case 'creditors':
                $data = collect($this->reportService->getCreditorsOutstanding($companyId)['creditors'])
                    ->map(function ($item) {
                        return [
                            'party' => $item['party']->name ?? '-',
                            'mobile' => $item['party']->mobile ?? '-',
                            'email' => $item['party']->email ?? '-',
                            'debit' => $item['debit'] ?? 0,
                            'credit' => $item['credit'] ?? 0,
                            'balance' => $item['balance'] ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
                break;

            case 'cash-book':
                $book = $this->reportService->getCashBankBook(
                    $companyId,
                    'cash',
                    isset($filters['account_id']) ? (int) $filters['account_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id
                );
                $ledger = $book['report'] ?? null;
                $data = $ledger
                    ? $ledger['entries']->map(function ($entry) {
                        return [
                            'transaction_date' => $entry->transaction_date,
                            'voucher_number' => $entry->voucher?->voucher_number ?? '',
                            'particulars' => $entry->description ?: ($entry->voucher?->narration ?? ''),
                            'receipts' => $entry->debit,
                            'payments' => $entry->credit,
                            'balance' => $entry->running_balance,
                        ];
                    })->values()->all()
                    : [];
                break;

            case 'profit-loss':
                $profitLoss = $this->reportService->getProfitLoss($companyId, $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id);
                $data = [
                    ['section' => 'Income', 'amount' => $profitLoss['income']['total']],
                    ['section' => 'Expense', 'amount' => $profitLoss['expense']['total']],
                    ['section' => $profitLoss['is_profit'] ? 'Net Profit' : 'Net Loss', 'amount' => abs($profitLoss['net_profit'])],
                ];
                break;

            case 'balance-sheet':
                $balanceSheet = $this->reportService->getBalanceSheet($companyId, $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id);
                $data = [
                    ['section' => 'Total Assets', 'amount' => $balanceSheet['assets']['total']],
                    ['section' => 'Total Liabilities', 'amount' => $balanceSheet['liabilities']['total']],
                    ['section' => 'Total Equity', 'amount' => $balanceSheet['equity']['total']],
                    ['section' => 'Liabilities + Equity', 'amount' => $balanceSheet['total_liabilities_equity']],
                    ['section' => 'Balanced', 'amount' => $balanceSheet['is_balanced'] ? 1 : 0],
                ];
                break;

            case 'trial-balance':
                $trialBalance = $this->ledgerService->getTrialBalance($companyId, $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id);
                $data = array_map(function ($row) {
                    return [
                        'account' => $row['account']->account_name,
                        'debit' => $row['debit'],
                        'credit' => $row['credit'],
                    ];
                }, $trialBalance['accounts']);
                break;

            case 'ledger':
                if (empty($filters['account_id'])) {
                    $data = [];
                    break;
                }
                $ledger = $this->ledgerService->getAccountLedger((int) $filters['account_id'], $companyId, $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id);
                $data = $ledger['entries']->map(function ($entry) {
                    return [
                        'transaction_date' => $entry->transaction_date,
                        'voucher_number' => $entry->voucher?->voucher_number ?? '',
                        'description' => $entry->voucher?->narration ?? '',
                        'debit' => $entry->debit,
                        'credit' => $entry->credit,
                        'balance' => $entry->running_balance,
                    ];
                })->values()->all();
                break;

            case 'day-book':
                $fyId = isset($filters['financial_year_id'])
                    ? (int) $filters['financial_year_id']
                    : \App\Models\FinancialYear::getCurrent($companyId)?->id;
                $dayBook = $this->reportService->getDayBook(
                    $companyId,
                    $filters['date'] ?? date('Y-m-d'),
                    $fyId
                );
                $data = collect($dayBook['rows'])->map(function ($row) {
                    return [
                        'voucher_number' => $row['voucher_number'],
                        'voucher_type' => ucfirst($row['voucher_type'] ?? '-'),
                        'particulars' => $row['account_name'] ?? '-',
                        'narration' => $row['narration'] ?? '-',
                        'debit' => $row['debit'],
                        'credit' => $row['credit'],
                    ];
                })->values()->all();
                break;
        }

        return Excel::raw(new GenericExport($data), \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export data to CSV
     */
    public function exportToCsv(string $type, int $companyId, array $filters = []): string
    {
        $data = [];

        switch ($type) {
            case 'accounts':
                $data = Account::where('company_id', $companyId)
                    ->orderBy('account_code')
                    ->get()
                    ->toArray();
                break;

            case 'parties':
                $query = Party::where('company_id', $companyId);
                if (isset($filters['type'])) {
                    $query->where('type', $filters['type']);
                }
                $data = $query->orderBy('name')->get()->toArray();
                break;

            case 'cash-book':
                $book = $this->reportService->getCashBankBook(
                    $companyId,
                    'cash',
                    isset($filters['account_id']) ? (int) $filters['account_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id
                );
                $ledger = $book['report'] ?? null;
                $data = $ledger
                    ? $ledger['entries']->map(function ($entry) {
                        return [
                            'transaction_date' => $entry->transaction_date,
                            'voucher_number' => $entry->voucher?->voucher_number ?? '',
                            'particulars' => $entry->description ?: ($entry->voucher?->narration ?? ''),
                            'receipts' => $entry->debit,
                            'payments' => $entry->credit,
                            'balance' => $entry->running_balance,
                        ];
                    })->values()->all()
                    : [];
                break;

            case 'profit-loss':
                $profitLoss = $this->reportService->getProfitLoss($companyId, $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id);
                $data = [
                    ['section' => 'Income', 'amount' => $profitLoss['income']['total']],
                    ['section' => 'Expense', 'amount' => $profitLoss['expense']['total']],
                    ['section' => $profitLoss['is_profit'] ? 'Net Profit' : 'Net Loss', 'amount' => abs($profitLoss['net_profit'])],
                ];
                break;

            case 'balance-sheet':
                $balanceSheet = $this->reportService->getBalanceSheet($companyId, $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id);
                $data = [
                    ['section' => 'Total Assets', 'amount' => $balanceSheet['assets']['total']],
                    ['section' => 'Total Liabilities', 'amount' => $balanceSheet['liabilities']['total']],
                    ['section' => 'Total Equity', 'amount' => $balanceSheet['equity']['total']],
                    ['section' => 'Liabilities + Equity', 'amount' => $balanceSheet['total_liabilities_equity']],
                    ['section' => 'Balanced', 'amount' => $balanceSheet['is_balanced'] ? 1 : 0],
                ];
                break;

            case 'vouchers':
                $query = Voucher::where('company_id', $companyId)
                    ->where('status', 'posted');
                if (isset($filters['voucher_type'])) {
                    $query->where('voucher_type', $filters['voucher_type']);
                }
                if (isset($filters['date_from'])) {
                    $query->where('voucher_date', '>=', $filters['date_from']);
                }
                if (isset($filters['date_to'])) {
                    $query->where('voucher_date', '<=', $filters['date_to']);
                }
                $data = $query->orderBy('voucher_date', 'desc')->get()->toArray();
                break;

            case 'debtors':
                $data = collect($this->reportService->getDebtorsOutstanding($companyId)['debtors'])
                    ->map(function ($item) {
                        return [
                            'party' => $item['party']->name ?? '-',
                            'mobile' => $item['party']->mobile ?? '-',
                            'email' => $item['party']->email ?? '-',
                            'debit' => $item['debit'] ?? 0,
                            'credit' => $item['credit'] ?? 0,
                            'balance' => $item['balance'] ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
                break;

            case 'creditors':
                $data = collect($this->reportService->getCreditorsOutstanding($companyId)['creditors'])
                    ->map(function ($item) {
                        return [
                            'party' => $item['party']->name ?? '-',
                            'mobile' => $item['party']->mobile ?? '-',
                            'email' => $item['party']->email ?? '-',
                            'debit' => $item['debit'] ?? 0,
                            'credit' => $item['credit'] ?? 0,
                            'balance' => $item['balance'] ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
                break;

            case 'trial-balance':
                $trialBalance = $this->ledgerService->getTrialBalance($companyId, $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id);
                $data = array_map(function ($row) {
                    return [
                        'account' => $row['account']->account_name,
                        'debit' => $row['debit'],
                        'credit' => $row['credit'],
                    ];
                }, $trialBalance['accounts']);
                break;

            case 'ledger':
                if (empty($filters['account_id'])) {
                    $data = [];
                    break;
                }
                $ledger = $this->ledgerService->getAccountLedger((int) $filters['account_id'], $companyId, $filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
                $data = $ledger['entries']->map(function ($entry) {
                    return [
                        'transaction_date' => $entry->transaction_date,
                        'voucher_number' => $entry->voucher?->voucher_number ?? '',
                        'description' => $entry->voucher?->narration ?? '',
                        'debit' => $entry->debit,
                        'credit' => $entry->credit,
                        'balance' => $entry->running_balance,
                    ];
                })->values()->all();
                break;

            case 'day-book':
                $fyId = isset($filters['financial_year_id'])
                    ? (int) $filters['financial_year_id']
                    : \App\Models\FinancialYear::getCurrent($companyId)?->id;
                $dayBook = $this->reportService->getDayBook(
                    $companyId,
                    $filters['date'] ?? date('Y-m-d'),
                    $fyId
                );
                $data = collect($dayBook['rows'])->map(function ($row) {
                    return [
                        'voucher_number' => $row['voucher_number'],
                        'voucher_type' => ucfirst($row['voucher_type'] ?? '-'),
                        'particulars' => $row['account_name'] ?? '-',
                        'narration' => $row['narration'] ?? '-',
                        'debit' => $row['debit'],
                        'credit' => $row['credit'],
                    ];
                })->values()->all();
                break;
        }

        $csv = implode(',', array_keys($data[0] ?? [])) . "\n";
        
        foreach ($data as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }
}
