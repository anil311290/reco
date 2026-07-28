<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
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
     * Cash Book / Bank Book (Tally style) for mode: cash | bank.
     * Bank book includes OD accounts.
     */
    public function getCashBankBook(
        int $companyId,
        string $mode,
        ?int $accountId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $financialYearId = null
    ): array {
        $modes = $mode === 'bank' ? ['bank', 'od'] : ['cash'];

        $accountsQuery = Account::where('company_id', $companyId)
            ->whereIn('transaction_mode', $modes)
            ->orderBy('account_code');

        $accounts = $accountsQuery->get();

        if ($accounts->isEmpty()) {
            return [
                'mode' => $mode,
                'accounts' => collect(),
                'account' => null,
                'report' => null,
                'message' => $mode === 'cash'
                    ? 'No Cash ledger found. Create an account with Payment Mode = Cash.'
                    : 'No Bank / OD ledger found. Create an account with Payment Mode = Bank or OD.',
            ];
        }

        $selectedAccount = $accountId
            ? $accounts->firstWhere('id', $accountId)
            : $accounts->first();

        if (!$selectedAccount) {
            $selectedAccount = $accounts->first();
        }

        $fyId = $financialYearId ?? FinancialYear::getCurrent($companyId)?->id;
        $ledger = $this->ledgerService->getAccountLedger(
            (int) $selectedAccount->id,
            $companyId,
            $fyId,
            $dateFrom,
            $dateTo
        );

        return [
            'mode' => $mode,
            'title' => $mode === 'cash' ? 'Cash Book' : 'Bank Book',
            'accounts' => $accounts,
            'account' => $selectedAccount,
            'report' => $ledger,
            'message' => null,
        ];
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
