<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Voucher;
use App\Interfaces\LedgerRepositoryInterface;
use Illuminate\Support\Facades\DB;

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
        // Get Income accounts
        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'income')
            ->where('is_active', true)
            ->get();

        $totalIncome = 0;
        $incomeDetails = [];

        foreach ($incomeAccounts as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            if ($balance['balance'] > 0) {
                $incomeDetails[] = [
                    'account' => $account,
                    'amount' => $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'],
                ];
                $totalIncome += $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'];
            }
        }

        // Get Expense accounts
        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'expense')
            ->where('is_active', true)
            ->get();

        $totalExpense = 0;
        $expenseDetails = [];

        foreach ($expenseAccounts as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            if ($balance['balance'] > 0) {
                $expenseDetails[] = [
                    'account' => $account,
                    'amount' => $balance['type'] === 'debit' ? $balance['balance'] : -$balance['balance'],
                ];
                $totalExpense += $balance['type'] === 'debit' ? $balance['balance'] : -$balance['balance'];
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
        // Assets
        $assetAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'asset')
            ->where('is_active', true)
            ->get();

        $totalAssets = 0;
        $assetDetails = [];

        foreach ($assetAccounts as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            if ($balance['balance'] > 0) {
                $assetDetails[] = [
                    'account' => $account,
                    'amount' => $balance['type'] === 'debit' ? $balance['balance'] : -$balance['balance'],
                ];
                $totalAssets += $balance['type'] === 'debit' ? $balance['balance'] : -$balance['balance'];
            }
        }

        // Liabilities
        $liabilityAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'liability')
            ->where('is_active', true)
            ->get();

        $totalLiabilities = 0;
        $liabilityDetails = [];

        foreach ($liabilityAccounts as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            if ($balance['balance'] > 0) {
                $liabilityDetails[] = [
                    'account' => $account,
                    'amount' => $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'],
                ];
                $totalLiabilities += $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'];
            }
        }

        // Equity
        $equityAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'equity')
            ->where('is_active', true)
            ->get();

        $totalEquity = 0;
        $equityDetails = [];

        foreach ($equityAccounts as $account) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);
            if ($balance['balance'] > 0) {
                $equityDetails[] = [
                    'account' => $account,
                    'amount' => $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'],
                ];
                $totalEquity += $balance['type'] === 'credit' ? $balance['balance'] : -$balance['balance'];
            }
        }

        // Add net profit to equity
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
     * Get Day Book report
     */
    public function getDayBook(int $companyId, string $date): array
    {
        // Use ledger entries as source of truth
        $entries = $this->ledgerRepository->getEntries([
            'company_id' => $companyId,
            'transaction_date' => $date,
        ], ['voucher', 'account']);

        // Collect voucher ids and build voucher list
        $voucherIds = $entries->pluck('voucher_id')->unique()->filter()->values()->all();
        $vouchers = Voucher::whereIn('id', $voucherIds)->with(['party', 'lines.account'])->orderBy('voucher_number')->get();

        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        return [
            'date' => $date,
            'vouchers' => $vouchers,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    /**
     * Get Debtors Outstanding report
     */
    public function getDebtorsOutstanding(int $companyId): array
    {
        $debtors = Party::where('company_id', $companyId)
            ->where('type', 'debtor')
            ->where('is_active', true)
            ->get();

        $outstanding = [];
        $totalOutstanding = 0;

        foreach ($debtors as $debtor) {
            // Sum ledger entries for vouchers linked to this party
            $entries = $this->ledgerRepository->getEntries(['company_id' => $companyId], ['voucher']);

            $debitTotal = $entries->filter(function ($e) use ($debtor) {
                return $e->voucher && $e->voucher->party_id === $debtor->id && in_array($e->voucher->voucher_type, ['income', 'receipt']);
            })->sum('debit');

            $creditTotal = $entries->filter(function ($e) use ($debtor) {
                return $e->voucher && $e->voucher->party_id === $debtor->id && in_array($e->voucher->voucher_type, ['payment', 'journal']);
            })->sum('credit');

            $balance = $debitTotal - $creditTotal;

            if ($balance > 0) {
                $outstanding[] = [
                    'party' => $debtor,
                    'debit' => $debitTotal,
                    'credit' => $creditTotal,
                    'balance' => $balance,
                ];
                $totalOutstanding += $balance;
            }
        }

        return [
            'debtors' => $outstanding,
            'total' => $totalOutstanding,
        ];
    }

    /**
     * Get Creditors Outstanding report
     */
    public function getCreditorsOutstanding(int $companyId): array
    {
        $creditors = Party::where('company_id', $companyId)
            ->where('type', 'creditor')
            ->where('is_active', true)
            ->get();

        $outstanding = [];
        $totalOutstanding = 0;

        foreach ($creditors as $creditor) {
            $entries = $this->ledgerRepository->getEntries(['company_id' => $companyId], ['voucher']);

            $creditTotal = $entries->filter(function ($e) use ($creditor) {
                return $e->voucher && $e->voucher->party_id === $creditor->id && in_array($e->voucher->voucher_type, ['expense', 'payment']);
            })->sum('credit');

            $debitTotal = $entries->filter(function ($e) use ($creditor) {
                return $e->voucher && $e->voucher->party_id === $creditor->id && in_array($e->voucher->voucher_type, ['receipt', 'journal']);
            })->sum('debit');

            $balance = $creditTotal - $debitTotal;

            if ($balance > 0) {
                $outstanding[] = [
                    'party' => $creditor,
                    'debit' => $debitTotal,
                    'credit' => $creditTotal,
                    'balance' => $balance,
                ];
                $totalOutstanding += $balance;
            }
        }

        return [
            'creditors' => $outstanding,
            'total' => $totalOutstanding,
        ];
    }

    /**
     * Get Cash Flow report
     */
    public function getCashFlow(int $companyId, int $financialYearId): array
    {
        // Get cash/bank accounts
        $cashAccounts = Account::where('company_id', $companyId)
            ->whereIn('account_name', ['Cash', 'Bank', 'Cash in Hand'])
            ->where('is_active', true)
            ->get();

        $inflows = 0;
        $outflows = 0;

        foreach ($cashAccounts as $account) {
            $entries = $this->ledgerRepository->getEntries([
                'company_id' => $companyId,
                'account_id' => $account->id,
                'financial_year_id' => $financialYearId,
            ]);

            $inflows += $entries->sum('debit');
            $outflows += $entries->sum('credit');
        }

        return [
            'inflows' => $inflows,
            'outflows' => $outflows,
            'net_cash_flow' => $inflows - $outflows,
        ];
    }
}
