<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\SalesInvoice;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\TaxRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;
use Illuminate\Support\Facades\Auth;

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
    public function exportProfitLossPdf(
        int $companyId,
        int $financialYearId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): string
    {
        $report = $this->reportService->getProfitLoss($companyId, $financialYearId, $dateFrom, $dateTo);
        $exportMeta = $this->buildExportMeta($companyId, 'profit-loss', [], $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.profit-loss', compact('report', 'exportMeta'));

        return $pdf->output();
    }

    /**
     * Export Balance Sheet to PDF
     */
    public function exportBalanceSheetPdf(
        int $companyId,
        int $financialYearId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): string
    {
        $report = $this->reportService->getBalanceSheet($companyId, $financialYearId, $dateFrom, $dateTo);
        $exportMeta = $this->buildExportMeta($companyId, 'balance-sheet', [], $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.balance-sheet', compact('report', 'exportMeta'));

        return $pdf->output();
    }

    /**
     * Export Trial Balance to PDF
     */
    public function exportTrialBalancePdf(
        int $companyId,
        int $financialYearId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): string
    {
        $report = $this->reportService->getTrialBalance($companyId, $financialYearId, $dateFrom, $dateTo);
        $exportMeta = $this->buildExportMeta($companyId, 'trial-balance', [], $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.trial-balance', compact('report', 'exportMeta'));

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
        $exportMeta = $this->buildExportMeta($companyId, 'ledger', ['account_id' => $accountId], $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.ledger', compact('report', 'exportMeta'));

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

        $exportMeta = $this->buildExportMeta((int) $voucher->company_id, 'voucher', ['voucher_id' => $voucherId]);

        $pdf = Pdf::loadView('exports.voucher', compact('voucher', 'exportMeta'));

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

        $exportMeta = $this->buildExportMeta(
            (int) $invoice->company_id,
            'sales-invoice',
            ['invoice_id' => $invoiceId],
            (int) ($invoice->financial_year_id ?? 0) ?: null,
            optional($invoice->invoice_date)->format('Y-m-d'),
            optional($invoice->due_date)->format('Y-m-d')
        );

        $pdf = Pdf::loadView('exports.sales-invoice', compact('invoice', 'exportMeta'));

        return $pdf->output();
    }

    /**
     * Export Debtors Outstanding to PDF
     */
    public function exportDebtorsOutstandingPdf(
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        array $filters = []
    ): string
    {
        $report = $this->reportService->getDebtorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo, $filters);
        $exportMeta = $this->buildExportMeta($companyId, 'debtors-outstanding', $filters, $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.debtors-outstanding', compact('report', 'exportMeta'));

        return $pdf->output();
    }

    /**
     * Export Creditors Outstanding to PDF
     */
    public function exportCreditorsOutstandingPdf(
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        array $filters = []
    ): string
    {
        $report = $this->reportService->getCreditorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo, $filters);
        $exportMeta = $this->buildExportMeta($companyId, 'creditors-outstanding', $filters, $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.creditors-outstanding', compact('report', 'exportMeta'));

        return $pdf->output();
    }

    /**
     * Export Aging Summary to PDF
     */
    public function exportAgingSummaryPdf(
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        array $filters = []
    ): string
    {
        $debtors = $this->reportService->getDebtorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo, $filters);
        $creditors = $this->reportService->getCreditorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo, $filters);

        $rows = $this->buildAgingSummaryRows($debtors['debtors'] ?? [], $creditors['creditors'] ?? []);
        $summary = [
            'receivables_total' => (float) ($debtors['total'] ?? 0),
            'payables_total' => (float) ($creditors['total'] ?? 0),
        ];
        $exportMeta = $this->buildExportMeta($companyId, 'aging-summary', $filters, $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.aging-summary', compact('rows', 'summary', 'dateFrom', 'dateTo', 'exportMeta'));

        return $pdf->output();
    }

    /**
     * Export Day Book to PDF
     */
    public function exportDayBookPdf(
        int $companyId,
        string $date,
        ?int $financialYearId = null,
        ?string $dateTo = null
    ): string
    {
        $financialYearId = $financialYearId ?? \App\Models\FinancialYear::getCurrent($companyId)?->id;
        $dateFrom = $date;
        $dateTo = $dateTo ?: $dateFrom;

        $report = $this->reportService->getDayBookRange(
            $companyId,
            $dateFrom,
            $dateTo,
            $financialYearId
        );
        $exportMeta = $this->buildExportMeta($companyId, 'day-book', [], $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.day-book', compact('report', 'dateFrom', 'dateTo', 'exportMeta'));

        return $pdf->output();
    }

    /**
     * Export Receipt & Payment to PDF
     */
    public function exportReceiptPaymentPdf(
        int $companyId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $financialYearId = null
    ): string {
        $report = $this->reportService->getReceiptPayment(
            $companyId,
            $dateFrom,
            $dateTo,
            $financialYearId ?? FinancialYear::getCurrent($companyId)?->id
        );
        $exportMeta = $this->buildExportMeta($companyId, 'receipt-payment', [], $financialYearId, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('exports.receipt-payment', compact('report', 'exportMeta'));

        return $pdf->output();
    }

    /**
     * Export master list to PDF.
     */
    public function exportMasterPdf(string $type, int $companyId, array $filters = []): string
    {
        $dataset = $this->getMasterExportDataset($type, $companyId, $filters);
        $exportMeta = $this->buildExportMeta($companyId, $type, $filters);

        $pdf = Pdf::loadView('exports.master-list', [
            'title' => $dataset['title'],
            'columns' => $dataset['columns'],
            'rows' => $dataset['rows'],
            'exportMeta' => $exportMeta,
        ]);

        return $pdf->output();
    }

    /**
     * Export data to Excel
     */
    public function exportToExcel(string $type, int $companyId, array $filters = []): string
    {
        $exportMeta = $this->buildExportMeta(
            $companyId,
            $type,
            $filters,
            isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
            $filters['date_from'] ?? ($filters['date'] ?? null),
            $filters['date_to'] ?? ($filters['date'] ?? null)
        );

        $masterTypes = ['accounts', 'parties', 'items', 'item-categories', 'tax-rates'];
        if (in_array($type, $masterTypes, true)) {
            $dataset = $this->getMasterExportDataset($type, $companyId, $filters);
            return Excel::raw(new GenericExport($this->withExcelMeta($dataset['rows'], $exportMeta)), \Maatwebsite\Excel\Excel::XLSX);
        }

        $data = [];

        switch ($type) {
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
                $data = collect($this->reportService->getDebtorsOutstanding(
                    $companyId,
                    isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters
                )['debtors'])
                    ->map(function ($item) {
                        return [
                            'Invoice No' => ($item['invoice_number'] ?? '-') . ' / ' . ($item['party']->party_code ?? '-'),
                            'Party' => $item['party']->name ?? '-',
                            'Invoice Date' => !empty($item['invoice_date']) ? \Carbon\Carbon::parse($item['invoice_date'])->format('d/m/Y') : '-',
                            'Due Date' => !empty($item['due_date']) ? \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') : '-',
                            'Billed Days' => $item['billed_days'] ?? 0,
                            'Due Days' => $item['due_days'] ?? 0,
                            'Amount' => $item['invoice_total'] ?? 0,
                            'Paid' => $item['amount_paid'] ?? 0,
                            'Balance' => $item['balance'] ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
                break;

            case 'creditors':
                $data = collect($this->reportService->getCreditorsOutstanding(
                    $companyId,
                    isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters
                )['creditors'])
                    ->map(function ($item) {
                        return [
                            'Invoice No' => ($item['invoice_number'] ?? '-') . ' / ' . ($item['party']->party_code ?? '-'),
                            'Party' => $item['party']->name ?? '-',
                            'Invoice Date' => !empty($item['invoice_date']) ? \Carbon\Carbon::parse($item['invoice_date'])->format('d/m/Y') : '-',
                            'Due Date' => !empty($item['due_date']) ? \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') : '-',
                            'Billed Days' => $item['billed_days'] ?? 0,
                            'Due Days' => $item['due_days'] ?? 0,
                            'Amount' => $item['invoice_total'] ?? 0,
                            'Paid' => $item['amount_paid'] ?? 0,
                            'Balance' => $item['balance'] ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
                break;

            case 'aging-summary':
                $debtors = $this->reportService->getDebtorsOutstanding(
                    $companyId,
                    isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters
                );
                $creditors = $this->reportService->getCreditorsOutstanding(
                    $companyId,
                    isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters
                );
                $data = $this->buildAgingSummaryRows($debtors['debtors'] ?? [], $creditors['creditors'] ?? []);
                break;

            case 'receipt-payment':
                $data = $this->receiptPaymentRows($companyId, $filters);
                break;

            case 'profit-loss':
                $profitLoss = $this->reportService->getProfitLoss(
                    $companyId,
                    (int) ($filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id),
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                );
                $data = [
                    ['section' => 'Income', 'amount' => $profitLoss['income']['total']],
                    ['section' => 'Expense', 'amount' => $profitLoss['expense']['total']],
                    ['section' => $profitLoss['is_profit'] ? 'Net Profit' : 'Net Loss', 'amount' => abs($profitLoss['net_profit'])],
                ];
                break;

            case 'balance-sheet':
                $balanceSheet = $this->reportService->getBalanceSheet(
                    $companyId,
                    (int) ($filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id),
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                );
                $data = [
                    ['section' => 'Total Assets', 'amount' => $balanceSheet['assets']['total']],
                    ['section' => 'Total Liabilities', 'amount' => $balanceSheet['liabilities']['total']],
                    ['section' => 'Total Equity', 'amount' => $balanceSheet['equity']['total']],
                    ['section' => 'Liabilities + Equity', 'amount' => $balanceSheet['total_liabilities_equity']],
                    ['section' => 'Balanced', 'amount' => $balanceSheet['is_balanced'] ? 1 : 0],
                ];
                break;

            case 'trial-balance':
                $trialBalance = $this->reportService->getTrialBalance(
                    $companyId,
                    (int) ($filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id),
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                );
                $data = array_map(function ($row) {
                    return [
                        'account_code' => $row['account']->account_code,
                        'account' => $row['account']->account_name,
                        'type' => $row['account']->account_type,
                        'destination' => $row['destination'] ?? '',
                        'opening_debit' => $row['opening_debit'] ?? 0,
                        'opening_credit' => $row['opening_credit'] ?? 0,
                        'transaction_debit' => $row['transaction_debit'] ?? 0,
                        'transaction_credit' => $row['transaction_credit'] ?? 0,
                        'closing_debit' => $row['debit'],
                        'closing_credit' => $row['credit'],
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
                        'particulars' => $entry->particulars ?? ($entry->voucher?->narration ?? ''),
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
                $from = $filters['date_from'] ?? ($filters['date'] ?? date('Y-m-d'));
                $to = $filters['date_to'] ?? $from;
                $dayBook = $this->reportService->getDayBookRange(
                    $companyId,
                    $from,
                    $to,
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

        return Excel::raw(new GenericExport($this->withExcelMeta($data, $exportMeta)), \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export data to CSV
     */
    public function exportToCsv(string $type, int $companyId, array $filters = []): string
    {
        $exportMeta = $this->buildExportMeta(
            $companyId,
            $type,
            $filters,
            isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
            $filters['date_from'] ?? ($filters['date'] ?? null),
            $filters['date_to'] ?? ($filters['date'] ?? null)
        );

        $masterTypes = ['accounts', 'parties', 'items', 'item-categories', 'tax-rates'];
        if (in_array($type, $masterTypes, true)) {
            $dataset = $this->getMasterExportDataset($type, $companyId, $filters);
            $data = $this->withExcelMeta($dataset['rows'], $exportMeta);
            $csv = implode(',', array_keys($data[0] ?? [])) . "\n";

            foreach ($data as $row) {
                $csv .= implode(',', array_map(function ($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }

            return $csv;
        }

        $data = [];

        switch ($type) {
            case 'receipt-payment':
                $data = $this->receiptPaymentRows($companyId, $filters);
                break;

            case 'profit-loss':
                $profitLoss = $this->reportService->getProfitLoss(
                    $companyId,
                    (int) ($filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id),
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                );
                $data = [
                    ['section' => 'Income', 'amount' => $profitLoss['income']['total']],
                    ['section' => 'Expense', 'amount' => $profitLoss['expense']['total']],
                    ['section' => $profitLoss['is_profit'] ? 'Net Profit' : 'Net Loss', 'amount' => abs($profitLoss['net_profit'])],
                ];
                break;

            case 'balance-sheet':
                $balanceSheet = $this->reportService->getBalanceSheet(
                    $companyId,
                    (int) ($filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id),
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                );
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
                $data = collect($this->reportService->getDebtorsOutstanding(
                    $companyId,
                    isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters
                )['debtors'])
                    ->map(function ($item) {
                        return [
                            'Invoice No' => ($item['invoice_number'] ?? '-') . ' / ' . ($item['party']->party_code ?? '-'),
                            'Party' => $item['party']->name ?? '-',
                            'Invoice Date' => !empty($item['invoice_date']) ? \Carbon\Carbon::parse($item['invoice_date'])->format('d/m/Y') : '-',
                            'Due Date' => !empty($item['due_date']) ? \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') : '-',
                            'Billed Days' => $item['billed_days'] ?? 0,
                            'Due Days' => $item['due_days'] ?? 0,
                            'Amount' => $item['invoice_total'] ?? 0,
                            'Paid' => $item['amount_paid'] ?? 0,
                            'Balance' => $item['balance'] ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
                break;

            case 'creditors':
                $data = collect($this->reportService->getCreditorsOutstanding(
                    $companyId,
                    isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters
                )['creditors'])
                    ->map(function ($item) {
                        return [
                            'Invoice No' => ($item['invoice_number'] ?? '-') . ' / ' . ($item['party']->party_code ?? '-'),
                            'Party' => $item['party']->name ?? '-',
                            'Invoice Date' => !empty($item['invoice_date']) ? \Carbon\Carbon::parse($item['invoice_date'])->format('d/m/Y') : '-',
                            'Due Date' => !empty($item['due_date']) ? \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') : '-',
                            'Billed Days' => $item['billed_days'] ?? 0,
                            'Due Days' => $item['due_days'] ?? 0,
                            'Amount' => $item['invoice_total'] ?? 0,
                            'Paid' => $item['amount_paid'] ?? 0,
                            'Balance' => $item['balance'] ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
                break;

            case 'aging-summary':
                $debtors = $this->reportService->getDebtorsOutstanding(
                    $companyId,
                    isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters
                );
                $creditors = $this->reportService->getCreditorsOutstanding(
                    $companyId,
                    isset($filters['financial_year_id']) ? (int) $filters['financial_year_id'] : null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                    $filters
                );
                $data = $this->buildAgingSummaryRows($debtors['debtors'] ?? [], $creditors['creditors'] ?? []);
                break;

            case 'trial-balance':
                $trialBalance = $this->reportService->getTrialBalance(
                    $companyId,
                    (int) ($filters['financial_year_id'] ?? FinancialYear::getCurrent($companyId)?->id),
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                );
                $data = array_map(function ($row) {
                    return [
                        'account_code' => $row['account']->account_code,
                        'account' => $row['account']->account_name,
                        'type' => $row['account']->account_type,
                        'destination' => $row['destination'] ?? '',
                        'opening_debit' => $row['opening_debit'] ?? 0,
                        'opening_credit' => $row['opening_credit'] ?? 0,
                        'transaction_debit' => $row['transaction_debit'] ?? 0,
                        'transaction_credit' => $row['transaction_credit'] ?? 0,
                        'closing_debit' => $row['debit'],
                        'closing_credit' => $row['credit'],
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
                        'particulars' => $entry->particulars ?? ($entry->voucher?->narration ?? ''),
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
                $from = $filters['date_from'] ?? ($filters['date'] ?? date('Y-m-d'));
                $to = $filters['date_to'] ?? $from;
                $dayBook = $this->reportService->getDayBookRange(
                    $companyId,
                    $from,
                    $to,
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

        $data = $this->withExcelMeta($data, $exportMeta);
        $csv = implode(',', array_keys($data[0] ?? [])) . "\n";
        
        foreach ($data as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }

    /**
     * Flat Receipt & Payment rows shared by the Excel and CSV exports.
     */
    protected function receiptPaymentRows(int $companyId, array $filters = []): array
    {
        $report = $this->reportService->getReceiptPayment(
            $companyId,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
            isset($filters['financial_year_id'])
                ? (int) $filters['financial_year_id']
                : FinancialYear::getCurrent($companyId)?->id
        );

        $rows = [];

        if (abs((float) ($report['opening_total'] ?? 0)) >= 0.01) {
            $rows[] = [
                'section' => 'Receipt',
                'particulars' => 'Opening Balance b/f',
                'amount' => $report['opening_total'],
            ];
        }

        foreach ($report['receipts']['rows'] as $row) {
            $rows[] = [
                'section' => 'Receipt',
                'particulars' => $row['label'],
                'amount' => $row['amount'],
            ];
        }

        $rows[] = [
            'section' => 'Receipt',
            'particulars' => 'Total Receipts',
            'amount' => $report['receipts_side_total'],
        ];

        foreach ($report['payments']['rows'] as $row) {
            $rows[] = [
                'section' => 'Payment',
                'particulars' => $row['label'],
                'amount' => $row['amount'],
            ];
        }

        $rows[] = [
            'section' => 'Payment',
            'particulars' => 'Closing Balance c/f',
            'amount' => $report['closing_total'],
        ];

        $rows[] = [
            'section' => 'Payment',
            'particulars' => 'Total Payments',
            'amount' => $report['payments_side_total'],
        ];

        return $rows;
    }

    protected function buildAgingSummaryRows(array $debtors, array $creditors): array
    {
        $rows = collect($debtors)
            ->map(function (array $item) {
                return [
                    'type' => 'Receivable',
                    'invoice_number' => $item['invoice_number'] ?? '-',
                    'party_code' => $item['party']->party_code ?? '-',
                    'party' => $item['party']->name ?? '-',
                    'invoice_date' => $item['invoice_date'] ?? '-',
                    'due_date' => $item['due_date'] ?? '-',
                    'billed_days' => $item['billed_days'] ?? 0,
                    'due_days' => $item['due_days'] ?? 0,
                    'balance' => $item['balance'] ?? 0,
                ];
            })
            ->merge(
                collect($creditors)->map(function (array $item) {
                return [
                    'type' => 'Payable',
                    'invoice_number' => $item['invoice_number'] ?? '-',
                    'party_code' => $item['party']->party_code ?? '-',
                    'party' => $item['party']->name ?? '-',
                    'invoice_date' => $item['invoice_date'] ?? '-',
                    'due_date' => $item['due_date'] ?? '-',
                    'billed_days' => $item['billed_days'] ?? 0,
                    'due_days' => $item['due_days'] ?? 0,
                    'balance' => $item['balance'] ?? 0,
                ];
                })
            )
            ->sort(function (array $a, array $b) {
                $daysCompare = (int) ($b['due_days'] ?? 0) <=> (int) ($a['due_days'] ?? 0);
                if ($daysCompare !== 0) {
                    return $daysCompare;
                }

                return (float) ($b['balance'] ?? 0) <=> (float) ($a['balance'] ?? 0);
            })
            ->values();

        return $rows->all();
    }

    protected function buildExportMeta(
        int $companyId,
        string $exportType,
        array $filters = [],
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $company = Company::find($companyId);
        $financialYear = $financialYearId
            ? FinancialYear::where('company_id', $companyId)->find($financialYearId)
            : null;
        $user = Auth::user();

        return [
            'company_name' => $company->name ?? 'N/A',
            'company_slug' => $company->slug ?? 'N/A',
            'company_gst' => $company->gst_number ?? 'N/A',
            'company_email' => $company->email ?? 'N/A',
            'company_phone' => $company->phone ?? 'N/A',
            'export_type' => strtoupper(str_replace('-', ' ', $exportType)),
            'financial_year' => $financialYear?->name ?? 'All',
            'date_from' => $dateFrom ?: 'N/A',
            'date_to' => $dateTo ?: 'N/A',
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'generated_by' => $user?->name ?? 'System',
            'generated_by_email' => $user?->email ?? 'N/A',
            'applied_filters' => $this->formatAppliedFilters($filters),
        ];
    }

    protected function withExcelMeta(array $rows, array $meta): array
    {
        $normalizedRows = empty($rows) ? [['data' => 'No rows']] : $rows;

        return array_map(function (array $row) use ($meta) {
            return array_merge([
                'Export Company' => $meta['company_name'] ?? 'N/A',
                'Report Type' => $meta['export_type'] ?? 'N/A',
                'Financial Year' => $meta['financial_year'] ?? 'All',
                'From Date' => $meta['date_from'] ?? 'N/A',
                'To Date' => $meta['date_to'] ?? 'N/A',
                'Generated At' => $meta['generated_at'] ?? 'N/A',
                'Generated By' => $meta['generated_by'] ?? 'System',
                'Applied Filters' => $meta['applied_filters'] ?? 'None',
            ], $row);
        }, $normalizedRows);
    }

    protected function formatAppliedFilters(array $filters): string
    {
        if (empty($filters)) {
            return 'None';
        }

        $excludedKeys = ['page', 'per_page', '_token'];
        $pairs = [];

        foreach ($filters as $key => $value) {
            if (in_array((string) $key, $excludedKeys, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $label = ucwords(str_replace(['_', '-'], ' ', (string) $key));
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }

            $pairs[] = $label . ': ' . (string) $value;
        }

        return empty($pairs) ? 'None' : implode(' | ', $pairs);
    }

    protected function getMasterExportDataset(string $type, int $companyId, array $filters = []): array
    {
        return match ($type) {
            'accounts' => $this->buildAccountsExportDataset($companyId, $filters),
            'parties' => $this->buildPartiesExportDataset($companyId, $filters),
            'items' => $this->buildItemsExportDataset($companyId, $filters),
            'item-categories' => $this->buildCategoriesExportDataset($companyId, $filters),
            'tax-rates' => $this->buildTaxRatesExportDataset($companyId, $filters),
            default => throw new \InvalidArgumentException("Unsupported export type [{$type}]"),
        };
    }

    protected function buildAccountsExportDataset(int $companyId, array $filters = []): array
    {
        $query = Account::query()->where('company_id', $companyId);

        if (!empty($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', (int) $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('account_name', 'like', '%' . $search . '%')
                    ->orWhere('account_code', 'like', '%' . $search . '%');
            });
        }

        $accounts = $query->orderBy('account_code')->get();

        return [
            'title' => 'Account Master Report',
            'columns' => ['Code', 'Name', 'Type', 'Is Cash/Bank/OD', 'Opening Balance', 'Balance Type', 'Opening Date', 'Status', 'Remarks'],
            'rows' => $accounts->map(fn (Account $account) => [
                'Code' => $account->account_code,
                'Name' => $account->account_name,
                'Type' => ucfirst($account->account_type),
                'Is Cash/Bank/OD' => $account->account_type === 'asset'
                    ? ($account->is_cash_bank_od ? 'Yes' : 'No')
                    : '-',
                'Opening Balance' => $account->opening_balance,
                'Balance Type' => ucfirst($account->balance_type ?? 'debit'),
                'Opening Date' => optional($account->opening_date)->format('d-M-Y') ?? '-',
                'Status' => $account->is_active ? 'Active' : 'Inactive',
                'Remarks' => $account->remarks ?: '-',
            ])->values()->all(),
        ];
    }

    protected function buildPartiesExportDataset(int $companyId, array $filters = []): array
    {
        $query = Party::query()->where('company_id', $companyId);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', (int) $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('party_code', 'like', '%' . $search . '%')
                    ->orWhere('mobile', 'like', '%' . $search . '%');
            });
        }

        $parties = $query->orderBy('name')->get();

        return [
            'title' => 'Party Master Report',
            'columns' => ['Code', 'Name', 'Type', 'Mobile', 'Email', 'GSTIN', 'Opening Balance', 'Balance Type', 'Status'],
            'rows' => $parties->map(fn (Party $party) => [
                'Code' => $party->party_code,
                'Name' => $party->name,
                'Type' => ucfirst($party->type),
                'Mobile' => $party->mobile ?: '-',
                'Email' => $party->email ?: '-',
                'GSTIN' => $party->gstin ?: '-',
                'Opening Balance' => $party->opening_balance,
                'Balance Type' => ucfirst($party->opening_balance_type ?? 'debit'),
                'Status' => $party->is_active ? 'Active' : 'Inactive',
            ])->values()->all(),
        ];
    }

    protected function buildItemsExportDataset(int $companyId, array $filters = []): array
    {
        $query = Item::query()
            ->with(['category', 'taxRate'])
            ->where('company_id', $companyId);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', (int) $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('item_code', 'like', '%' . $search . '%')
                    ->orWhere('barcode', 'like', '%' . $search . '%');
            });
        }

        $items = $query->orderBy('name')->get();

        return [
            'title' => 'Items and Services Report',
            'columns' => ['Code', 'Name', 'Category', 'Type', 'HSN/SAC', 'Selling Price', 'Current Stock', 'Tax', 'Status'],
            'rows' => $items->map(fn (Item $item) => [
                'Code' => $item->item_code,
                'Name' => $item->name,
                'Category' => $item->category?->name ?? '-',
                'Type' => ucfirst($item->type),
                'HSN/SAC' => $item->hsn_sac_code ?: '-',
                'Selling Price' => $item->selling_price,
                'Current Stock' => $item->is_stockable ? $item->current_stock : 'N/A',
                'Tax' => $item->taxRate?->tax_name ?? '-',
                'Status' => $item->is_active ? 'Active' : 'Inactive',
            ])->values()->all(),
        ];
    }

    protected function buildCategoriesExportDataset(int $companyId, array $filters = []): array
    {
        $query = ItemCategory::query()
            ->withCount('items')
            ->where('company_id', $companyId);

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', (int) $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return [
            'title' => 'Item Category Master Report',
            'columns' => ['Name', 'Description', 'Sort Order', 'Items Count', 'Status'],
            'rows' => $categories->map(fn (ItemCategory $category) => [
                'Name' => $category->name,
                'Description' => $category->description ?: '-',
                'Sort Order' => $category->sort_order,
                'Items Count' => $category->items_count,
                'Status' => $category->is_active ? 'Active' : 'Inactive',
            ])->values()->all(),
        ];
    }

    protected function buildTaxRatesExportDataset(int $companyId, array $filters = []): array
    {
        $query = TaxRate::query()->where('company_id', $companyId);

        if (!empty($filters['tax_category'])) {
            $query->where('tax_category', $filters['tax_category']);
        }
        if (!empty($filters['tax_type'])) {
            $query->where('tax_type', $filters['tax_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('tax_name', 'like', '%' . $search . '%')
                    ->orWhere('tax_code', 'like', '%' . $search . '%');
            });
        }

        $rates = $query->orderBy('tax_name')->get();

        return [
            'title' => 'Tax Master Report',
            'columns' => ['Tax Code', 'Tax Name', 'Category', 'Type', 'Rate', 'Status', 'Notes'],
            'rows' => $rates->map(fn (TaxRate $rate) => [
                'Tax Code' => $rate->tax_code ?: '-',
                'Tax Name' => $rate->tax_name,
                'Category' => $rate->tax_category,
                'Type' => ucfirst($rate->tax_type),
                'Rate' => $rate->tax_rate,
                'Status' => ucfirst($rate->status),
                'Notes' => $rate->notes ?: '-',
            ])->values()->all(),
        ];
    }
}
