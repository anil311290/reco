<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\SalesInvoice;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\TaxRate;
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
     * Export Cash Book to PDF
     */
    public function exportCashBookPdf(
        int $companyId,
        ?int $accountId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $financialYearId = null
    ): string {
        $report = $this->reportService->getCashBankBook(
            $companyId,
            'cash',
            $accountId,
            $dateFrom,
            $dateTo,
            $financialYearId ?? FinancialYear::getCurrent($companyId)?->id
        );

        $title = 'Cash Book';
        $pdf = Pdf::loadView('exports.cash-bank-book', compact('report', 'title'));

        return $pdf->output();
    }

    /**
     * Export Bank Book to PDF
     */
    public function exportBankBookPdf(
        int $companyId,
        ?int $accountId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $financialYearId = null
    ): string {
        $report = $this->reportService->getCashBankBook(
            $companyId,
            'bank',
            $accountId,
            $dateFrom,
            $dateTo,
            $financialYearId ?? FinancialYear::getCurrent($companyId)?->id
        );

        $title = 'Bank Book';
        $pdf = Pdf::loadView('exports.cash-bank-book', compact('report', 'title'));

        return $pdf->output();
    }

    /**
     * Export master list to PDF.
     */
    public function exportMasterPdf(string $type, int $companyId, array $filters = []): string
    {
        $dataset = $this->getMasterExportDataset($type, $companyId, $filters);

        $pdf = Pdf::loadView('exports.master-list', [
            'title' => $dataset['title'],
            'columns' => $dataset['columns'],
            'rows' => $dataset['rows'],
        ]);

        return $pdf->output();
    }

    /**
     * Export data to Excel
     */
    public function exportToExcel(string $type, int $companyId, array $filters = []): string
    {
        $masterTypes = ['accounts', 'parties', 'items', 'item-categories', 'tax-rates'];
        if (in_array($type, $masterTypes, true)) {
            $dataset = $this->getMasterExportDataset($type, $companyId, $filters);
            return Excel::raw(new GenericExport($dataset['rows']), \Maatwebsite\Excel\Excel::XLSX);
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

            case 'bank-book':
                $book = $this->reportService->getCashBankBook(
                    $companyId,
                    'bank',
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
        $masterTypes = ['accounts', 'parties', 'items', 'item-categories', 'tax-rates'];
        if (in_array($type, $masterTypes, true)) {
            $dataset = $this->getMasterExportDataset($type, $companyId, $filters);
            $data = $dataset['rows'];
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

            case 'bank-book':
                $book = $this->reportService->getCashBankBook(
                    $companyId,
                    'bank',
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
            'columns' => ['Code', 'Name', 'Type', 'Mode', 'Opening Balance', 'Balance Type', 'Opening Date', 'Status', 'Remarks'],
            'rows' => $accounts->map(fn (Account $account) => [
                'Code' => $account->account_code,
                'Name' => $account->account_name,
                'Type' => ucfirst($account->account_type),
                'Mode' => $account->transaction_mode_label ?? ($account->transaction_mode ?: '-'),
                'Opening Balance' => $account->opening_balance,
                'Balance Type' => ucfirst($account->balance_type ?? 'debit'),
                'Opening Date' => optional($account->opening_date)->format('d-m-Y') ?? '-',
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
