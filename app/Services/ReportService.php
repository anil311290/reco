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
            ->where('is_active', true)
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
            ->where('is_active', true)
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
    public function getDayBook(int $companyId, string $date): array
    {
        $vouchers = Voucher::where('company_id', $companyId)
            ->whereDate('voucher_date', $date)
            ->where('status', 'posted')
            ->with(['party', 'lines.account'])
            ->orderBy('voucher_number')
            ->get();

        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($vouchers as $voucher) {
            foreach ($voucher->lines as $line) {
                $debit = (float) $line->debit;
                $credit = (float) $line->credit;
                $totalDebit += $debit;
                $totalCredit += $credit;

                $rows[] = [
                    'voucher' => $voucher,
                    'voucher_number' => $voucher->voucher_number,
                    'voucher_type' => $voucher->voucher_type,
                    'account_name' => $line->account?->account_name ?? '-',
                    'party_name' => $voucher->party?->name,
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
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
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
            ->where('is_active', true)
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
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->orderBy('name')
            ->get();

        $outstanding = [];
        $totalOutstanding = 0;

        foreach ($debtors as $debtor) {
            if (!$financialYearId) {
                continue;
            }

            $balance = $this->ledgerService->getAccountBalance(
                (int) $debtor->account_id,
                $companyId,
                $financialYearId
            );

            // Receivable = debit balance on party account
            $amount = $balance['type'] === 'debit'
                ? (float) $balance['balance']
                : -1 * (float) $balance['balance'];

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
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->orderBy('name')
            ->get();

        $outstanding = [];
        $totalOutstanding = 0;

        foreach ($creditors as $creditor) {
            if (!$financialYearId) {
                continue;
            }

            $balance = $this->ledgerService->getAccountBalance(
                (int) $creditor->account_id,
                $companyId,
                $financialYearId
            );

            // Payable = credit balance on party account
            $amount = $balance['type'] === 'credit'
                ? (float) $balance['balance']
                : -1 * (float) $balance['balance'];

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
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
    }
}
