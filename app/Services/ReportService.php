<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\Voucher;
use App\Interfaces\LedgerRepositoryInterface;

class ReportService
{
    protected LedgerService $ledgerService;
    protected LedgerRepositoryInterface $ledgerRepository;

    public function __construct(LedgerService $ledgerService, LedgerRepositoryInterface $ledgerRepository)
    {
        $this->ledgerService = $ledgerService;
        $this->ledgerRepository = $ledgerRepository;
    }

    /**
     * Get Profit & Loss report
     */
    public function getProfitLoss(int $companyId, int $financialYearId): array
    {
        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'income')
            ->orderBy('account_code')
            ->get();

        $totalIncome = 0;
        $incomeDetails = [];

        foreach ($incomeAccounts as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            $amount = $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'];

            if (abs($amount) >= 0.01) {
                $incomeDetails[] = [
                    'account' => $account,
                    'amount' => $amount,
                ];
                $totalIncome += $amount;
            }
        }

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'expense')
            ->orderBy('account_code')
            ->get();

        $totalExpense = 0;
        $expenseDetails = [];

        foreach ($expenseAccounts as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            $amount = $balance['type'] === 'debit' ? $balance['balance'] : -$balance['balance'];

            if (abs($amount) >= 0.01) {
                $expenseDetails[] = [
                    'account' => $account,
                    'amount' => $amount,
                ];
                $totalExpense += $amount;
            }
        }

        $netProfit = $totalIncome - $totalExpense;

        return [
            'income' => [
                'accounts' => $incomeDetails,
                'total' => $totalIncome,
            ],
            'expense' => [
                'accounts' => $expenseDetails,
                'total' => $totalExpense,
            ],
            'net_profit' => $netProfit,
            'is_profit' => $netProfit >= 0,
        ];
    }

    /**
     * Get Balance Sheet report
     */
    public function getBalanceSheet(int $companyId, int $financialYearId): array
    {
        $assetDetails = [];
        $totalAssets = 0;
        foreach ($this->activeAccountsByType($companyId, 'asset') as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            $amount = $balance['type'] === 'debit' ? $balance['balance'] : -$balance['balance'];
            if (abs($amount) >= 0.01) {
                $assetDetails[] = ['account' => $account, 'amount' => $amount];
                $totalAssets += $amount;
            }
        }

        $liabilityDetails = [];
        $totalLiabilities = 0;
        foreach ($this->activeAccountsByType($companyId, 'liability') as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            $amount = $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'];
            if (abs($amount) >= 0.01) {
                $liabilityDetails[] = ['account' => $account, 'amount' => $amount];
                $totalLiabilities += $amount;
            }
        }

        $equityDetails = [];
        $totalEquity = 0;
        foreach ($this->activeAccountsByType($companyId, 'equity') as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            $amount = $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'];
            if (abs($amount) >= 0.01) {
                $equityDetails[] = ['account' => $account, 'amount' => $amount];
                $totalEquity += $amount;
            }
        }

        $profitLoss = $this->getProfitLoss($companyId, $financialYearId);
        $totalEquity += $profitLoss['net_profit'];

        return [
            'assets' => [
                'accounts' => $assetDetails,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilityDetails,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equityDetails,
                'total' => $totalEquity,
                'net_profit' => $profitLoss['net_profit'],
            ],
            'total_liabilities_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Day Book — posted vouchers for a date with line particulars (Tally style).
     */
    public function getDayBook(int $companyId, string $date, ?int $financialYearId = null): array
    {
        // Tally Day Book sequence: chronological voucher entry order for the day,
        // then each voucher's lines (sort_order → debit lines → credit lines → id).
        $query = Voucher::where('company_id', $companyId)
            ->whereDate('voucher_date', $date)
            ->where('status', 'posted')
            ->with([
                'party',
                'lines.account',
                'lines.party',
                'salesInvoice',
                'purchaseInvoice',
            ])
            ->orderBy('created_at')
            ->orderBy('id');

        if ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        $vouchers = $query->get();

        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $serial = 0;

        foreach ($vouchers as $voucher) {
            $lines = $voucher->lines
                ->sortBy([
                    ['sort_order', 'asc'],
                    fn ($line) => ((float) $line->debit > 0 ? 0 : 1),
                    ['id', 'asc'],
                ])
                ->values();

            foreach ($lines as $line) {
                $debit = (float) $line->debit;
                $credit = (float) $line->credit;
                $totalDebit += $debit;
                $totalCredit += $credit;
                $serial++;

                $rows[] = [
                    'serial' => $serial,
                    'voucher' => $voucher,
                    'voucher_id' => $voucher->id,
                    'voucher_number' => $voucher->voucher_number,
                    'voucher_type' => $voucher->voucher_type,
                    'account_id' => $line->account_id,
                    'account_name' => $line->account?->account_name ?? '-',
                    'party_id' => $line->party_id ?: $voucher->party_id,
                    'party_name' => $line->party?->name ?? $voucher->party?->name,
                    'sales_invoice_id' => $voucher->sales_invoice_id,
                    'purchase_invoice_id' => $voucher->purchase_invoice_id,
                    'narration' => $line->description ?: $voucher->narration,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            }
        }

        return [
            'date' => $date,
            'vouchers' => $vouchers,
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
        ];
    }

    /**
     * Receipt & Payment account — every cash, bank, and OD movement of a period
     * grouped by the contra ledger head, with opening and closing balances.
     *
     * Opening + Receipts always equals Payments + Closing: each voucher's cash
     * movement is attributed to its non-cash heads, so transfers between two
     * cash / bank ledgers cancel out and never inflate either side.
     */
    public function getReceiptPayment(
        int $companyId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $financialYearId = null
    ): array {
        $financialYear = $financialYearId
            ? FinancialYear::where('company_id', $companyId)->find($financialYearId)
            : FinancialYear::getCurrent($companyId);

        $dateFrom = $dateFrom ?: $financialYear?->start_date?->format('Y-m-d');
        $dateTo = $dateTo ?: $financialYear?->end_date?->format('Y-m-d');

        $cashAccounts = Account::query()
            ->where('company_id', $companyId)
            ->cashBankOd()
            ->orderBy('account_code')
            ->get();

        if ($cashAccounts->isEmpty()) {
            return [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'financial_year_id' => $financialYear?->id,
                'accounts' => [],
                'receipts' => ['rows' => [], 'total' => 0.0],
                'payments' => ['rows' => [], 'total' => 0.0],
                'opening_total' => 0.0,
                'closing_total' => 0.0,
                'receipts_side_total' => 0.0,
                'payments_side_total' => 0.0,
                'is_balanced' => true,
                'message' => 'No Cash / Bank / OD ledger found. Enable Is Cash/Bank/OD on at least one asset ledger first.',
            ];
        }

        $cashAccountIds = $cashAccounts->pluck('id')->all();
        $fyId = $financialYear?->id;

        $entries = Ledger::where('company_id', $companyId)
            ->whereIn('account_id', $cashAccountIds)
            ->when($fyId, fn ($query) => $query->where('financial_year_id', $fyId))
            ->when($dateFrom, fn ($query) => $query->where('transaction_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->where('transaction_date', '<=', $dateTo))
            ->get(['id', 'account_id', 'voucher_id', 'debit', 'credit', 'description', 'reference_type']);

        $accountRows = [];
        $openingTotal = 0.0;
        $closingTotal = 0.0;

        foreach ($cashAccounts as $account) {
            $opening = $this->ledgerService->getOpeningBalance(
                (int) $account->id,
                $companyId,
                $fyId,
                $dateFrom
            );

            $openingAmount = $opening['type'] === 'credit'
                ? -1 * (float) $opening['balance']
                : (float) $opening['balance'];

            $accountEntries = $entries->where('account_id', $account->id);
            $received = round((float) $accountEntries->sum('debit'), 2);
            $paid = round((float) $accountEntries->sum('credit'), 2);
            $closingAmount = round($openingAmount + $received - $paid, 2);

            // Hide ledgers with no movement in the selected report period.
            if (abs($received) < 0.01 && abs($paid) < 0.01) {
                continue;
            }

            $accountRows[] = [
                'account' => $account,
                'opening' => round($openingAmount, 2),
                'received' => $received,
                'paid' => $paid,
                'closing' => $closingAmount,
            ];

            $openingTotal += $openingAmount;
            $closingTotal += $closingAmount;
        }

        [$receiptRows, $paymentRows] = $this->splitReceiptPaymentHeads($entries, $cashAccountIds);

        $receiptsTotal = round(array_sum(array_column($receiptRows, 'amount')), 2);
        $paymentsTotal = round(array_sum(array_column($paymentRows, 'amount')), 2);
        $openingTotal = round($openingTotal, 2);
        $closingTotal = round($closingTotal, 2);

        $receiptsSide = round($openingTotal + $receiptsTotal, 2);
        $paymentsSide = round($paymentsTotal + $closingTotal, 2);

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'financial_year_id' => $fyId,
            'accounts' => $accountRows,
            'receipts' => ['rows' => $receiptRows, 'total' => $receiptsTotal],
            'payments' => ['rows' => $paymentRows, 'total' => $paymentsTotal],
            'opening_total' => $openingTotal,
            'closing_total' => $closingTotal,
            'receipts_side_total' => $receiptsSide,
            'payments_side_total' => $paymentsSide,
            'is_balanced' => abs($receiptsSide - $paymentsSide) < 0.01,
            'message' => null,
        ];
    }

    /**
     * Attribute cash / bank movement to the contra heads of each voucher.
     *
     * @param  \Illuminate\Support\Collection<int, Ledger>  $entries
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    protected function splitReceiptPaymentHeads($entries, array $cashAccountIds): array
    {
        $voucherIds = $entries->pluck('voucher_id')->filter()->unique()->values();

        $contraByVoucher = $voucherIds->isEmpty()
            ? collect()
            : Ledger::whereIn('voucher_id', $voucherIds)
                ->whereNotIn('account_id', $cashAccountIds)
                ->with('account:id,account_code,account_name,account_type')
                ->get(['id', 'voucher_id', 'account_id', 'debit', 'credit'])
                ->groupBy('voucher_id');

        $receiptHeads = [];
        $paymentHeads = [];

        foreach ($contraByVoucher as $voucherLines) {
            foreach ($voucherLines as $line) {
                if (!$line->account) {
                    continue;
                }

                $key = 'account:' . $line->account_id;

                // Credit on the contra head funds the cash movement (receipt).
                if ((float) $line->credit > 0) {
                    $receiptHeads[$key] ??= [
                        'account' => $line->account,
                        'code' => $line->account->account_code,
                        'label' => $line->account->account_name,
                        'amount' => 0.0,
                    ];
                    $receiptHeads[$key]['amount'] += (float) $line->credit;
                }

                // Debit on the contra head consumes cash movement (payment).
                if ((float) $line->debit > 0) {
                    $paymentHeads[$key] ??= [
                        'account' => $line->account,
                        'code' => $line->account->account_code,
                        'label' => $line->account->account_name,
                        'amount' => 0.0,
                    ];
                    $paymentHeads[$key]['amount'] += (float) $line->debit;
                }
            }
        }

        foreach ($entries->whereNull('voucher_id') as $entry) {
            $label = str_contains((string) $entry->reference_type, 'opening')
                ? 'Opening Balance Entries'
                : ($entry->description ?: 'Unclassified');

            $key = 'direct:' . $label;
            if ((float) $entry->debit > 0) {
                $receiptHeads[$key] ??= [
                    'account' => null,
                    'code' => '',
                    'label' => $label,
                    'amount' => 0.0,
                ];
                $receiptHeads[$key]['amount'] += (float) $entry->debit;
            }

            if ((float) $entry->credit > 0) {
                $paymentHeads[$key] ??= [
                    'account' => null,
                    'code' => '',
                    'label' => $label,
                    'amount' => 0.0,
                ];
                $paymentHeads[$key]['amount'] += (float) $entry->credit;
            }
        }

        $normalize = static function (array $heads): array {
            $rows = [];
            foreach ($heads as $head) {
                $amount = round((float) ($head['amount'] ?? 0), 2);
                if ($amount < 0.01) {
                    continue;
                }
                $head['amount'] = $amount;
                $rows[] = $head;
            }

            return $rows;
        };

        $receipts = $normalize($receiptHeads);
        $payments = $normalize($paymentHeads);

        $sorter = static fn (array $a, array $b) => [$a['code'], $a['label']] <=> [$b['code'], $b['label']];
        usort($receipts, $sorter);
        usort($payments, $sorter);

        return [$receipts, $payments];
    }

    /**
     * Receivables outstanding from party-linked account balances.
     */
    public function getDebtorsOutstanding(int $companyId): array
    {
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        $debtors = Party::where('company_id', $companyId)
            ->where('type', 'debtor')
            ->orderBy('name')
            ->get();

        $outstanding = [];
        $totalOutstanding = 0;

        foreach ($debtors as $debtor) {
            if (!$financialYearId) {
                continue;
            }

            $ledger = $this->ledgerService->getPartyLedger(
                (int) $debtor->id,
                $companyId,
                $financialYearId
            );

            // Receivable = net debit balance tagged to this party in AR
            $amount = $ledger['closing_type'] === 'debit'
                ? (float) $ledger['closing_balance']
                : -1 * (float) $ledger['closing_balance'];

            if ($amount > 0.01) {
                $outstanding[] = [
                    'party' => $debtor,
                    'account_id' => $debtor->account_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => $amount,
                ];
                $totalOutstanding += $amount;
            }
        }

        return [
            'debtors' => $outstanding,
            'total' => $totalOutstanding,
        ];
    }

    /**
     * Payables outstanding from party-linked account balances.
     */
    public function getCreditorsOutstanding(int $companyId): array
    {
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        $creditors = Party::where('company_id', $companyId)
            ->where('type', 'creditor')
            ->orderBy('name')
            ->get();

        $outstanding = [];
        $totalOutstanding = 0;

        foreach ($creditors as $creditor) {
            if (!$financialYearId) {
                continue;
            }

            $ledger = $this->ledgerService->getPartyLedger(
                (int) $creditor->id,
                $companyId,
                $financialYearId
            );

            // Payable = net credit balance tagged to this party in AP
            $amount = $ledger['closing_type'] === 'credit'
                ? (float) $ledger['closing_balance']
                : -1 * (float) $ledger['closing_balance'];

            if ($amount > 0.01) {
                $outstanding[] = [
                    'party' => $creditor,
                    'account_id' => $creditor->account_id,
                    'debit' => 0,
                    'credit' => $amount,
                    'balance' => $amount,
                ];
                $totalOutstanding += $amount;
            }
        }

        return [
            'creditors' => $outstanding,
            'total' => $totalOutstanding,
        ];
    }

    protected function activeAccountsByType(int $companyId, string $type)
    {
        return Account::where('company_id', $companyId)
            ->where('account_type', $type)
            ->orderBy('account_code')
            ->get();
    }
}
