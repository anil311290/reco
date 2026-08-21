<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\PaymentInvoiceMapping;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Voucher;
use App\Interfaces\LedgerRepositoryInterface;
use Carbon\Carbon;

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
     * Get Balance Sheet report as of a single point-in-time date.
     */
    public function getBalanceSheet(
        int $companyId,
        int $financialYearId,
        ?string $asOfDate = null
    ): array
    {
        $assetDetails = [];
        $totalAssets = 0;
        foreach ($this->activeAccountsByType($companyId, 'asset') as $account) {
            $ledger = $this->ledgerService->getAccountLedger(
                (int) $account->id,
                $companyId,
                $financialYearId,
                null,
                $asOfDate
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
                null,
                $asOfDate
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
                null,
                $asOfDate
            );
            $closing = $ledger['closing_balance'];
            $amount = $closing['type'] === 'credit' ? $closing['balance'] : -$closing['balance'];
            if (abs($amount) >= 0.01) {
                $equityDetails[] = ['account' => $account, 'amount' => $amount];
                $totalEquity += $amount;
            }
        }

        // Net profit must span the full FY-to-date (not a partial window) so
        // it stays consistent with the cumulative asset/liability closing balances.
        $profitLoss = $this->getProfitLoss($companyId, $financialYearId, null, $asOfDate);
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
            'as_of_date' => $asOfDate,
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
            if (str_starts_with((string) $voucher->narration, '[OB:')) {
                continue;
            }

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
                    'party_id' => $line->party_id,
                    'party_name' => $line->party?->name,
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
            if (str_starts_with((string) $voucher->narration, '[OB:')) {
                continue;
            }

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
                    'party_id' => $line->party_id,
                    'party_name' => $line->party?->name,
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
     * Net ledger balance for a party (debit - credit) as of a given date.
     * Positive = net debit balance, negative = net credit balance.
     */
    public function getPartyNetLedgerBalance(int $companyId, int $partyId, ?int $financialYearId, ?string $asOfDate): float
    {
        $asOf = $asOfDate ?: now()->toDateString();

        $row = Ledger::query()
            ->where('company_id', $companyId)
            ->where('party_id', $partyId)
            ->when($financialYearId, fn ($q) => $q->where('financial_year_id', $financialYearId))
            ->whereDate('transaction_date', '<=', $asOf)
            ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
            ->first();

        return round((float) ($row->debit_total ?? 0) - (float) ($row->credit_total ?? 0), 2);
    }

    /**
    * Unapplied/advance amount for a party: money received (or paid, for creditors)
     * that has not yet been allocated to any specific invoice.
     *
     * Derived as: sum of that party's currently open invoice balances minus their
     * net ledger balance (in the party-type-normal direction). Any shortfall means
     * cash was received/paid beyond what open invoices account for — i.e. an advance.
     */
    public function getPartyUnbilledAmount(
        int $companyId,
        int $partyId,
        string $partyType,
        float $openInvoiceBalanceTotal,
        ?int $financialYearId = null,
        ?string $asOfDate = null
    ): float {
        $net = $this->getPartyNetLedgerBalance($companyId, $partyId, $financialYearId, $asOfDate);
        $normalizedNet = $partyType === 'debtor' ? $net : -$net;

        $unbilled = round($openInvoiceBalanceTotal - $normalizedNet, 2);

        return $unbilled > 0.01 ? $unbilled : 0.0;
    }

    /**
     * Return the portion of a party opening balance that is not allocated to bills.
     */
    public function getPartyOpeningBalanceAvailable(
        int $companyId,
        int $partyId,
        ?int $financialYearId = null,
        ?string $asOfDate = null
    ): float {
        $query = Voucher::query()
            ->where('company_id', $companyId)
            ->where('voucher_type', 'adjustment')
            ->where('narration', 'like', sprintf('[OB:party:%d]%%', $partyId))
            ->where('status', 'posted');

        if ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        if ($asOfDate) {
            $query->whereDate('voucher_date', '<=', Carbon::parse($asOfDate)->toDateString());
        }

        $voucher = $query->latest('id')->first();
        if (!$voucher) {
            return 0.0;
        }

        $allocated = PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $voucher->id)
            ->where('status', '!=', 'reversed')
            ->sum('amount_settled');

        return round(max(0, (float) $voucher->total_debit - (float) $allocated), 2);
    }

    /**
     * List posted receipt/payment vouchers that still have an unapplied balance.
     */
    public function getUnappliedReceiptsAndPayments(int $companyId, ?string $asOfDate = null): array
    {
        $asOf = Carbon::parse($asOfDate ?: now()->toDateString())->toDateString();

        return Voucher::query()
            ->with('party')
            ->where('company_id', $companyId)
            ->whereIn('voucher_type', ['receipt', 'payment'])
            ->where('status', 'posted')
            ->whereNotNull('party_id')
            ->whereDate('voucher_date', '<=', $asOf)
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Voucher $voucher) {
                $allocated = (float) $voucher->paymentInvoiceMappings()
                    ->where('status', '!=', 'reversed')
                    ->sum('amount_settled');
                $unapplied = round((float) $voucher->total_debit - $allocated, 2);

                return [
                    'voucher' => $voucher,
                    'party' => $voucher->party,
                    'voucher_id' => $voucher->id,
                    'voucher_number' => $voucher->voucher_number,
                    'voucher_date' => $voucher->voucher_date?->toDateString(),
                    'voucher_type' => $voucher->voucher_type,
                    'reference_number' => $voucher->reference_number,
                    'voucher_amount' => (float) $voucher->total_debit,
                    'allocated_amount' => round($allocated, 2),
                    'unapplied_amount' => $unapplied,
                    'invoice_type' => $voucher->voucher_type === 'receipt' ? 'sales' : 'purchase',
                ];
            })
            ->filter(fn (array $row) => $row['unapplied_amount'] > 0.01)
            ->values()
            ->all();
    }

    /**
     * Receivables outstanding from party-linked account balances.
     */
    public function getDebtorsOutstanding(
        int $companyId,
        ?int $financialYearId = null,
        ?string $asOfDate = null,
        array $filters = []
    ): array
    {
        $filterMeta = $this->normalizeOutstandingFilters($asOfDate, $filters);
        [$debtors, $total] = $this->buildOutstandingInvoiceRows(
            SalesInvoice::class,
            'debtor',
            $companyId,
            $financialYearId,
            $filterMeta
        );

        return [
            'debtors' => $debtors,
            'total' => $total,
            'as_of_date' => $filterMeta['as_of_date'],
            'financial_year_id' => $financialYearId,
            'filters' => $filterMeta,
        ];
    }

    /**
     * Payables outstanding, invoice level.
     */
    public function getCreditorsOutstanding(
        int $companyId,
        ?int $financialYearId = null,
        ?string $asOfDate = null,
        array $filters = []
    ): array
    {
        $filterMeta = $this->normalizeOutstandingFilters($asOfDate, $filters);
        [$creditors, $total] = $this->buildOutstandingInvoiceRows(
            PurchaseInvoice::class,
            'creditor',
            $companyId,
            $financialYearId,
            $filterMeta
        );

        return [
            'creditors' => $creditors,
            'total' => $total,
            'as_of_date' => $filterMeta['as_of_date'],
            'financial_year_id' => $financialYearId,
            'filters' => $filterMeta,
        ];
    }

    /**
     * Build invoice-level outstanding rows for a party type (AR = Sales, AP = Purchase).
     * Each row represents a single outstanding invoice, evaluated as of a single date.
     *
     * @return array{0: array, 1: float}
     */
    private function buildOutstandingInvoiceRows(
        string $invoiceModel,
        string $partyType,
        int $companyId,
        ?int $financialYearId,
        array $filterMeta
    ): array
    {
        $asOf = Carbon::parse($filterMeta['as_of_date'])->startOfDay();
        $basis = $filterMeta['basis'] ?? 'due';

        $query = $invoiceModel::query()
            ->with('party')
            ->where('company_id', $companyId)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('balance_due', '>', 0)
            ->whereDate('invoice_date', '<=', $asOf->toDateString())
            ->whereHas('party', function ($q) use ($partyType) {
                $q->where('type', $partyType);
            });

        $invoices = $query->orderBy('due_date')->orderBy('invoice_date')->get();

        $rows = [];
        $total = 0.0;

        foreach ($invoices as $invoice) {
            $invoiceDate = $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->startOfDay() : null;
            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->startOfDay() : null;
            $isDue = $dueDate !== null && $dueDate->lte($asOf);
            $overdueDays = ($dueDate !== null && $dueDate->lt($asOf)) ? (int) $dueDate->diffInDays($asOf) : 0;
            $balance = (float) $invoice->balance_due;

            $billedDays = $invoiceDate ? (int) $invoiceDate->diffInDays($asOf) : 0;

            $dueDays = null;
            if ($dueDate !== null) {
                $dueDays = (int) $dueDate->diffInDays($asOf);
            }

            // Bucket basis follows the selected toggle: Billed Days (since invoice date)
            // or Due Days (days past the due date, the traditional overdue-aging basis).
            $basisDays = $basis === 'billed' ? $billedDays : $overdueDays;

            $aging = [
                'oldest_due_date' => $dueDate?->toDateString(),
                'overdue_days' => $overdueDays,
                'overdue_amount' => $overdueDays > 0 ? round($balance, 2) : 0.0,
                'basis_days' => $basisDays,
                'age_bucket' => $this->resolveAgeBucket($basisDays),
                'overdue_status' => $isDue ? 'due' : 'not_due',
                'overdue_label' => $overdueDays > 0
                    ? ($overdueDays . ' days late')
                    : ($isDue ? 'Due Today' : 'Not Due'),
                'billed_days' => $billedDays,
                'due_days' => $dueDays,
            ];

            if (!$this->matchesOutstandingFilters($aging, $filterMeta)) {
                continue;
            }

            // NEW: Fetch settlement details for this invoice
            $settlements = $this->getInvoiceSettlementsForRow($partyType === 'debtor' ? 'sales' : 'purchase', $invoice->id);

            $rows[] = [
                'invoice' => $invoice,
                'party' => $invoice->party,
                'account_id' => $invoice->account_id,
                'invoice_id' => $invoice->id,
                'invoice_number' => (string) $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date?->toDateString(),
                'due_date' => $dueDate?->toDateString(),
                'invoice_total' => (float) $invoice->total,
                'amount_paid' => (float) $invoice->amount_paid,
                'debit' => $partyType === 'debtor' ? $balance : 0,
                'credit' => $partyType === 'creditor' ? $balance : 0,
                'balance' => $balance,
                'settlements' => $settlements, // NEW: Payment/Receipt details
                ...$aging,
            ];

            $total += $balance;
        }

        return [$rows, round($total, 2)];
    }

    /**
     * Get invoice settlements (payments/receipts) for report row display.
     * Returns array of payments that have settled this invoice.
     *
     * @param string $invoiceType 'sales' or 'purchase'
     * @param int $invoiceId
     * @return array
     */
    private function getInvoiceSettlementsForRow(string $invoiceType, int $invoiceId): array
    {
        $mappingClass = 'App\Models\PaymentInvoiceMapping';
        if (!class_exists($mappingClass)) {
            return [];
        }

        $mappingModel = $mappingClass;
        $mappings = $mappingModel::where('invoice_type', $invoiceType)
            ->where('invoice_id', $invoiceId)
            ->where('status', '!=', 'reversed')
            ->with('paymentVoucher')
            ->orderBy('created_at', 'asc')
            ->get();

        $settlements = [];
        foreach ($mappings as $mapping) {
            $settlements[] = [
                'voucher_number' => $mapping->paymentVoucher?->voucher_number ?? 'N/A',
                'voucher_date' => $mapping->paymentVoucher?->voucher_date?->toDateString(),
                'voucher_type' => $mapping->paymentVoucher?->voucher_type,
                'amount_settled' => (float) $mapping->amount_settled,
                'status' => $mapping->status,
            ];
        }

        return $settlements;
    }

    private function normalizeOutstandingFilters(?string $asOfDate, array $filters): array
    {
        $overdueStatus = (string) ($filters['overdue_status'] ?? 'all');
        if (!in_array($overdueStatus, ['all', 'due', 'not_due'], true)) {
            $overdueStatus = 'all';
        }

        $ageBucket = (string) ($filters['age_bucket'] ?? 'all');
        if (!in_array($ageBucket, ['all', 'current', '1_30', '31_60', '61_90', '91_plus', 'custom'], true)) {
            $ageBucket = 'all';
        }

        $basis = (string) ($filters['basis'] ?? 'due');
        if (!in_array($basis, ['billed', 'due'], true)) {
            $basis = 'due';
        }

        $ageMin = null;
        $ageMax = null;

        if ($ageBucket === 'custom') {
            if (isset($filters['age_min']) && $filters['age_min'] !== '') {
                $ageMin = max(0, (int) $filters['age_min']);
            }

            if (isset($filters['age_max']) && $filters['age_max'] !== '') {
                $ageMax = max(0, (int) $filters['age_max']);
            }

            if ($ageMin === null && $ageMax === null) {
                $ageBucket = 'all';
            }
        }

        $asOf = $asOfDate ? Carbon::parse($asOfDate)->startOfDay() : now()->startOfDay();

        return [
            'overdue_status' => $overdueStatus,
            'age_bucket' => $ageBucket,
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'basis' => $basis,
            'as_of_date' => $asOf->toDateString(),
        ];
    }

    private function resolveAgeBucket(int $days): string
    {
        if ($days <= 0) {
            return 'current';
        }

        if ($days <= 30) {
            return '1_30';
        }

        if ($days <= 60) {
            return '31_60';
        }

        if ($days <= 90) {
            return '61_90';
        }

        return '91_plus';
    }

    private function matchesOutstandingFilters(array $aging, array $filters): bool
    {
        $overdueStatus = (string) ($filters['overdue_status'] ?? 'all');
        $rowStatus = (string) ($aging['overdue_status'] ?? 'not_due');
        $ageBucket = (string) ($filters['age_bucket'] ?? 'all');
        $basisDays = (int) ($aging['basis_days'] ?? $aging['overdue_days'] ?? 0);

        if ($overdueStatus === 'due' && $rowStatus !== 'due') {
            return false;
        }

        if ($overdueStatus === 'not_due' && $rowStatus !== 'not_due') {
            return false;
        }

        if ($ageBucket === 'all') {
            return true;
        }

        if ($ageBucket === 'current') {
            return $basisDays <= 0;
        }

        if ($ageBucket === 'custom') {
            $min = $filters['age_min'] ?? null;
            $max = $filters['age_max'] ?? null;

            if ($min !== null && $basisDays < (int) $min) {
                return false;
            }

            if ($max !== null && $basisDays > (int) $max) {
                return false;
            }

            return true;
        }

        return ($aging['age_bucket'] ?? 'current') === $ageBucket;
    }

    protected function activeAccountsByType(int $companyId, string $type)
    {
        return Account::where('company_id', $companyId)
            ->where('account_type', $type)
            ->orderBy('account_code')
            ->get();
    }

    /**
     * Get settlement details for a specific invoice.
     * Shows all payments/receipts that settled this invoice.
     *
     * @param string $invoiceType 'sales' or 'purchase'
     * @param int $invoiceId
     * @return array
     */
    public function getInvoiceSettlementDetails(string $invoiceType, int $invoiceId): array
    {
        $mappingClass = 'App\Models\PaymentInvoiceMapping';
        if (!class_exists($mappingClass)) {
            return [
                'invoice_type' => $invoiceType,
                'invoice_id' => $invoiceId,
                'settlements' => [],
                'total_allocated' => 0.0,
                'total_settled' => 0.0,
                'outstanding' => 0.0,
                'message' => 'Settlement tracking not yet enabled',
            ];
        }

        $mappingModel = $mappingClass;
        $mappings = $mappingModel::where('invoice_type', $invoiceType)
            ->where('invoice_id', $invoiceId)
            ->where('status', '!=', 'reversed')
            ->with('paymentVoucher')
            ->orderBy('created_at', 'asc')
            ->get();

        $totalAllocated = 0.0;
        $totalSettled = 0.0;
        $settlements = [];

        foreach ($mappings as $mapping) {
            $totalAllocated += (float) $mapping->amount_allocated;
            $totalSettled += (float) $mapping->amount_settled;

            $settlements[] = [
                'mapping_id' => $mapping->id,
                'payment_voucher_id' => $mapping->payment_voucher_id,
                'voucher_number' => $mapping->paymentVoucher?->voucher_number ?? 'N/A',
                'voucher_date' => $mapping->paymentVoucher?->voucher_date?->toDateString(),
                'voucher_type' => $mapping->paymentVoucher?->voucher_type,
                'amount_allocated' => (float) $mapping->amount_allocated,
                'amount_settled' => (float) $mapping->amount_settled,
                'outstanding' => (float) $mapping->getOutstandingAmount(),
                'status' => $mapping->status,
                'created_at' => $mapping->created_at?->toDateTimeString(),
                'notes' => $mapping->notes,
            ];
        }

        return [
            'invoice_type' => $invoiceType,
            'invoice_id' => $invoiceId,
            'settlements' => $settlements,
            'total_allocated' => round($totalAllocated, 2),
            'total_settled' => round($totalSettled, 2),
            'outstanding' => round($totalAllocated - $totalSettled, 2),
        ];
    }

    /**
     * Get settlement details for a specific payment/receipt voucher.
     * Shows all invoices settled by this payment.
     *
     * @param int $voucherId
     * @return array
     */
    public function getPaymentSettlementDetails(int $voucherId): array
    {
        $mappingClass = 'App\Models\PaymentInvoiceMapping';
        if (!class_exists($mappingClass)) {
            return [
                'voucher_id' => $voucherId,
                'invoices_settled' => [],
                'total_allocated' => 0.0,
                'total_settled' => 0.0,
                'message' => 'Settlement tracking not yet enabled',
            ];
        }

        $voucher = Voucher::find($voucherId);
        if (!$voucher) {
            return [
                'voucher_id' => $voucherId,
                'invoices_settled' => [],
                'total_allocated' => 0.0,
                'total_settled' => 0.0,
                'message' => 'Voucher not found',
            ];
        }

        $mappingModel = $mappingClass;
        $mappings = $mappingModel::where('payment_voucher_id', $voucherId)
            ->where('status', '!=', 'reversed')
            ->orderBy('created_at', 'asc')
            ->get();

        $totalAllocated = 0.0;
        $totalSettled = 0.0;
        $invoicesSettled = [];

        foreach ($mappings as $mapping) {
            $totalAllocated += (float) $mapping->amount_allocated;
            $totalSettled += (float) $mapping->amount_settled;

            // Get invoice details based on type
            if ($mapping->invoice_type === 'sales') {
                $invoice = SalesInvoice::find($mapping->invoice_id);
                $invoiceNumber = $invoice?->invoice_number ?? 'N/A';
                $invoiceDate = $invoice?->invoice_date?->toDateString();
                $partyName = $invoice?->party?->name ?? 'N/A';
            } else {
                $invoice = PurchaseInvoice::find($mapping->invoice_id);
                $invoiceNumber = $invoice?->invoice_number ?? 'N/A';
                $invoiceDate = $invoice?->invoice_date?->toDateString();
                $partyName = $invoice?->party?->name ?? 'N/A';
            }

            $invoicesSettled[] = [
                'mapping_id' => $mapping->id,
                'invoice_type' => $mapping->invoice_type,
                'invoice_id' => $mapping->invoice_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'party_name' => $partyName,
                'invoice_original_balance' => (float) $mapping->invoice_original_balance,
                'amount_allocated' => (float) $mapping->amount_allocated,
                'amount_settled' => (float) $mapping->amount_settled,
                'outstanding' => (float) $mapping->getOutstandingAmount(),
                'status' => $mapping->status,
                'notes' => $mapping->notes,
            ];
        }

        return [
            'voucher_id' => $voucherId,
            'voucher_number' => $voucher->voucher_number,
            'voucher_date' => $voucher->voucher_date?->toDateString(),
            'voucher_type' => $voucher->voucher_type,
            'party_name' => $voucher->party?->name ?? 'N/A',
            'invoices_settled' => $invoicesSettled,
            'total_allocated' => round($totalAllocated, 2),
            'total_settled' => round($totalSettled, 2),
            'outstanding' => round($totalAllocated - $totalSettled, 2),
        ];
    }

    /**
     * Get comprehensive settlement audit report.
     * Shows all payment-invoice mappings within a date range and company.
     *
     * @param int $companyId
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     * @param array $filters ['status' => 'pending|partial|full|reversed|all', 'type' => 'sales|purchase|all']
     * @return array
     */
    public function getSettlementAuditReport(
        int $companyId,
        ?Carbon $dateFrom = null,
        ?Carbon $dateTo = null,
        array $filters = []
    ): array
    {
        $mappingClass = 'App\Models\PaymentInvoiceMapping';
        if (!class_exists($mappingClass)) {
            return [
                'company_id' => $companyId,
                'date_from' => $dateFrom?->toDateString(),
                'date_to' => $dateTo?->toDateString(),
                'mappings' => [],
                'summary' => [
                    'total_mappings' => 0,
                    'total_allocated' => 0.0,
                    'total_settled' => 0.0,
                    'total_outstanding' => 0.0,
                    'by_status' => [],
                    'by_type' => [],
                ],
                'message' => 'Settlement tracking not yet enabled',
            ];
        }

        $mappingModel = $mappingClass;
        $query = $mappingModel::where('company_id', $companyId);

        // Filter by status
        $statusFilter = $filters['status'] ?? 'all';
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // Filter by invoice type
        $typeFilter = $filters['type'] ?? 'all';
        if ($typeFilter !== 'all') {
            $query->where('invoice_type', $typeFilter);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom->toDateString());
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo->toDateString());
        }

        $mappings = $query
            ->with('paymentVoucher')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAllocated = 0.0;
        $totalSettled = 0.0;
        $byStatus = [];
        $byType = [];
        $mappingRows = [];

        foreach ($mappings as $mapping) {
            $totalAllocated += (float) $mapping->amount_allocated;
            $totalSettled += (float) $mapping->amount_settled;

            // Count by status
            $status = $mapping->status;
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;

            // Count by type
            $type = $mapping->invoice_type;
            $byType[$type] = ($byType[$type] ?? 0) + 1;

            // Get invoice and party details
            if ($mapping->invoice_type === 'sales') {
                $invoice = SalesInvoice::find($mapping->invoice_id);
                $partyName = $invoice?->party?->name ?? 'Unknown';
            } else {
                $invoice = PurchaseInvoice::find($mapping->invoice_id);
                $partyName = $invoice?->party?->name ?? 'Unknown';
            }

            $mappingRows[] = [
                'id' => $mapping->id,
                'uuid' => $mapping->uuid,
                'payment_voucher_number' => $mapping->paymentVoucher?->voucher_number ?? 'N/A',
                'payment_date' => $mapping->paymentVoucher?->voucher_date?->toDateString(),
                'invoice_type' => $mapping->invoice_type,
                'invoice_id' => $mapping->invoice_id,
                'invoice_number' => $invoice?->invoice_number ?? 'N/A',
                'invoice_date' => $invoice?->invoice_date?->toDateString(),
                'party_name' => $partyName,
                'amount_allocated' => (float) $mapping->amount_allocated,
                'amount_settled' => (float) $mapping->amount_settled,
                'outstanding' => (float) $mapping->getOutstandingAmount(),
                'status' => $mapping->status,
                'created_at' => $mapping->created_at?->toDateString(),
                'created_by_user' => optional($mapping->createdByUser)->name ?? 'N/A',
                'notes' => $mapping->notes,
            ];
        }

        return [
            'company_id' => $companyId,
            'date_from' => $dateFrom?->toDateString(),
            'date_to' => $dateTo?->toDateString(),
            'filters_applied' => [
                'status' => $statusFilter,
                'type' => $typeFilter,
            ],
            'mappings' => $mappingRows,
            'summary' => [
                'total_mappings' => count($mappings),
                'total_allocated' => round($totalAllocated, 2),
                'total_settled' => round($totalSettled, 2),
                'total_outstanding' => round($totalAllocated - $totalSettled, 2),
                'by_status' => $byStatus,
                'by_type' => $byType,
            ],
        ];
    }
}
