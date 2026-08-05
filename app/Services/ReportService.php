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
    public function getProfitLoss(
        int $companyId,
        int $financialYearId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array
    {
        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'income')
            ->orderBy('account_code')
            ->get();

        $totalIncome = 0;
        $incomeDetails = [];

        foreach ($incomeAccounts as $account) {
            $ledger = $this->ledgerService->getAccountLedger(
                (int) $account->id,
                $companyId,
                $financialYearId,
                $dateFrom,
                $dateTo
            );

            $amount = round((float) $ledger['total_credit'] - (float) $ledger['total_debit'], 2);

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
            $ledger = $this->ledgerService->getAccountLedger(
                (int) $account->id,
                $companyId,
                $financialYearId,
                $dateFrom,
                $dateTo
            );

            $amount = round((float) $ledger['total_debit'] - (float) $ledger['total_credit'], 2);

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
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * Get Balance Sheet report
     */
    public function getBalanceSheet(
        int $companyId,
        int $financialYearId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array
    {
        $assetDetails = [];
        $totalAssets = 0;
        foreach ($this->activeAccountsByType($companyId, 'asset') as $account) {
            $ledger = $this->ledgerService->getAccountLedger(
                (int) $account->id,
                $companyId,
                $financialYearId,
                $dateFrom,
                $dateTo
            );
            $closing = $ledger['closing_balance'];
            $amount = $closing['type'] === 'debit' ? $closing['balance'] : -$closing['balance'];
            if (abs($amount) >= 0.01) {
                $assetDetails[] = ['account' => $account, 'amount' => $amount];
                $totalAssets += $amount;
            }
        }

        $liabilityDetails = [];
        $totalLiabilities = 0;
        foreach ($this->activeAccountsByType($companyId, 'liability') as $account) {
            $ledger = $this->ledgerService->getAccountLedger(
                (int) $account->id,
                $companyId,
                $financialYearId,
                $dateFrom,
                $dateTo
            );
            $closing = $ledger['closing_balance'];
            $amount = $closing['type'] === 'credit' ? $closing['balance'] : -$closing['balance'];
            if (abs($amount) >= 0.01) {
                $liabilityDetails[] = ['account' => $account, 'amount' => $amount];
                $totalLiabilities += $amount;
            }
        }

        $equityDetails = [];
        $totalEquity = 0;
        foreach ($this->activeAccountsByType($companyId, 'equity') as $account) {
            $ledger = $this->ledgerService->getAccountLedger(
                (int) $account->id,
                $companyId,
                $financialYearId,
                $dateFrom,
                $dateTo
            );
            $closing = $ledger['closing_balance'];
            $amount = $closing['type'] === 'credit' ? $closing['balance'] : -$closing['balance'];
            if (abs($amount) >= 0.01) {
                $equityDetails[] = ['account' => $account, 'amount' => $amount];
                $totalEquity += $amount;
            }
        }

        $profitLoss = $this->getProfitLoss($companyId, $financialYearId, $dateFrom, $dateTo);
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
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * Trial Balance for selected date range in a financial year.
     */
    public function getTrialBalance(
        int $companyId,
        int $financialYearId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $accounts = Account::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

        $trialBalance = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $totalOpeningDebit = 0.0;
        $totalOpeningCredit = 0.0;
        $totalTransactionDebit = 0.0;
        $totalTransactionCredit = 0.0;

        foreach ($accounts as $account) {
            $ledger = $this->ledgerService->getAccountLedger(
                (int) $account->id,
                $companyId,
                $financialYearId,
                $dateFrom,
                $dateTo
            );

            $opening = $ledger['opening_balance'];
            $closing = $ledger['closing_balance'];

            $openingBalance = round((float) ($opening['balance'] ?? 0), 2);
            $closingBalance = round((float) ($closing['balance'] ?? 0), 2);
            $openingType = (string) ($opening['type'] ?? 'debit');
            $closingType = (string) ($closing['type'] ?? 'debit');

            $transactionDebit = round((float) ($ledger['total_debit'] ?? 0), 2);
            $transactionCredit = round((float) ($ledger['total_credit'] ?? 0), 2);

            $openingDebit = $openingType === 'debit' ? $openingBalance : 0.0;
            $openingCredit = $openingType === 'credit' ? $openingBalance : 0.0;
            $closingDebit = $closingType === 'debit' ? $closingBalance : 0.0;
            $closingCredit = $closingType === 'credit' ? $closingBalance : 0.0;

            $hasMovement = $openingDebit > 0.001
                || $openingCredit > 0.001
                || $transactionDebit > 0.001
                || $transactionCredit > 0.001
                || $closingDebit > 0.001
                || $closingCredit > 0.001;

            if (!$hasMovement) {
                continue;
            }

            if ((string) $account->account_code === Account::CODE_SUSPENSE && $closingDebit < 0.001 && $closingCredit < 0.001) {
                continue;
            }
            $destination = in_array($account->account_type, ['income', 'expense'], true) ? 'PL' : 'BS';

            $trialBalance[] = [
                'account' => $account,
                'type' => $account->account_type,
                'destination' => $destination,
                'opening_debit' => $openingDebit,
                'opening_credit' => $openingCredit,
                'opening_balance' => $openingBalance,
                'opening_type' => $openingType,
                'transaction_debit' => $transactionDebit,
                'transaction_credit' => $transactionCredit,
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit,
                'debit' => $closingDebit,
                'credit' => $closingCredit,
            ];

            $totalOpeningDebit += $openingDebit;
            $totalOpeningCredit += $openingCredit;
            $totalTransactionDebit += $transactionDebit;
            $totalTransactionCredit += $transactionCredit;
            $totalDebit += $closingDebit;
            $totalCredit += $closingCredit;
        }

        return [
            'accounts' => $trialBalance,
            'total_opening_debit' => round($totalOpeningDebit, 2),
            'total_opening_credit' => round($totalOpeningCredit, 2),
            'total_transaction_debit' => round($totalTransactionDebit, 2),
            'total_transaction_credit' => round($totalTransactionCredit, 2),
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
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
                    'voucher_date' => $voucher->voucher_date,
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
            'date_from' => $date,
            'date_to' => $date,
            'vouchers' => $vouchers,
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
        ];
    }

    /**
     * Day Book for date range.
     */
    public function getDayBookRange(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $financialYearId = null
    ): array {
        if ($dateFrom === $dateTo) {
            return $this->getDayBook($companyId, $dateFrom, $financialYearId);
        }

        $query = Voucher::where('company_id', $companyId)
            ->whereBetween('voucher_date', [$dateFrom, $dateTo])
            ->where('status', 'posted')
            ->with([
                'party',
                'lines.account',
                'lines.party',
                'salesInvoice',
                'purchaseInvoice',
            ])
            ->orderBy('voucher_date')
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
                    'voucher_date' => $voucher->voucher_date,
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
            'date' => $dateFrom,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
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

        [$receiptRows, $receiptSuspenseTotal] = $this->extractOpeningDifferenceRows($receiptRows);
        [$paymentRows, $paymentSuspenseTotal] = $this->extractOpeningDifferenceRows($paymentRows);

        // Treat Opening Balance as part of opening, not period head movement.
        $openingTotal = round($openingTotal + $receiptSuspenseTotal - $paymentSuspenseTotal, 2);

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
    * Remove Opening Balance heads from side rows and return their total.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    protected function extractOpeningDifferenceRows(array $rows): array
    {
        $filtered = [];
        $suspenseTotal = 0.0;

        foreach ($rows as $row) {
            $isSuspense = (string) ($row['code'] ?? '') === Account::CODE_SUSPENSE
                || strtolower((string) ($row['label'] ?? '')) === 'opening balance';

            if ($isSuspense) {
                $suspenseTotal += (float) ($row['amount'] ?? 0);
                continue;
            }

            $filtered[] = $row;
        }

        return [$filtered, round($suspenseTotal, 2)];
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
            $label = ((bool) ($entry->is_opening_balance ?? false) || str_contains((string) $entry->reference_type, 'opening'))
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
    public function getDebtorsOutstanding(
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array
    {
        $financialYearId = $financialYearId ?: FinancialYear::getCurrent($companyId)?->id;

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
                $financialYearId,
                $dateFrom,
                $dateTo
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
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'financial_year_id' => $financialYearId,
        ];
    }

    /**
     * Payables outstanding from party-linked account balances.
     */
    public function getCreditorsOutstanding(
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array
    {
        $financialYearId = $financialYearId ?: FinancialYear::getCurrent($companyId)?->id;

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
                $financialYearId,
                $dateFrom,
                $dateTo
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
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'financial_year_id' => $financialYearId,
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
