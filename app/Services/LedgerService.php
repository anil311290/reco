<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\Voucher;
use App\Interfaces\LedgerRepositoryInterface;
use App\Interfaces\AccountRepositoryInterface;
use App\Services\LedgerPartyHistoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LedgerService
{
    protected LedgerRepositoryInterface $ledgerRepository;
    protected AccountRepositoryInterface $accountRepository;
    protected LedgerPartyHistoryService $historyService;

    public function __construct(
        LedgerRepositoryInterface $ledgerRepository,
        AccountRepositoryInterface $accountRepository,
        LedgerPartyHistoryService $historyService
    ) {
        $this->ledgerRepository = $ledgerRepository;
        $this->accountRepository = $accountRepository;
        $this->historyService = $historyService;
    }

    /**
     * Generate ledger entries for a posted voucher
     */
    public function generateForVoucher(Voucher $voucher): void
    {
        if ($voucher->status !== 'posted') {
            throw new \Exception('Only posted vouchers can generate ledger entries.');
        }

        try {
            DB::beginTransaction();

            // Delete existing ledger entries for this voucher
            $this->ledgerRepository->deleteByVoucher($voucher->id);

            foreach ($voucher->lines as $line) {
                $this->createEntry(
                    $voucher->company_id,
                    $voucher->financial_year_id,
                    $line->account_id,
                    $voucher->id,
                    $voucher->voucher_date->format('Y-m-d'),
                    'voucher',
                    $voucher->id,
                    $voucher->party_id,
                    $line->description ?? $voucher->narration,
                    $line->debit,
                    $line->credit,
                    $voucher->created_by,
                    $voucher->created_by_ip
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a single ledger entry
     */
    public function createEntry(
        int $companyId,
        ?int $financialYearId,
        int $accountId,
        ?int $voucherId,
        string $transactionDate,
        string $referenceType,
        ?int $referenceId,
        ?int $partyId,
        ?string $description,
        float $debit,
        float $credit,
        ?int $createdBy = null,
        ?string $createdByIp = null
    ): Ledger {
        // Calculate running balance
        $lastEntry = $this->ledgerRepository->getLastEntry($companyId, $accountId);

        // Get account to determine balance calculation and opening balance
        $account = $this->accountRepository->find($accountId);
        
        if ($lastEntry) {
            $previousBalance = $lastEntry->running_balance;
        } else {
            // Use account opening balance if no previous ledger entry
            $previousBalance = $account->opening_balance ?? 0;
        }

        $isDebitNormal = in_array($account->account_type, ['asset', 'expense']);

        // Calculate new balance
        if ($isDebitNormal) {
            $newBalance = $previousBalance + $debit - $credit;
        } else {
            $newBalance = $previousBalance + $credit - $debit;
        }

        $balanceType = $newBalance >= 0 ? 'debit' : 'credit';
        $runningBalance = abs($newBalance);

        $ledger = $this->ledgerRepository->create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'account_id' => $accountId,
            'party_id' => $partyId,
            'voucher_id' => $voucherId,
            'transaction_date' => $transactionDate,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => $runningBalance,
            'balance_type' => $balanceType,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'created_by_ip' => $createdByIp,
            'updated_by_ip' => $createdByIp,
        ]);

        if ($partyId) {
            $party = Party::find($partyId);
            if ($party) {
                $this->historyService->logHistory($ledger, $party, $referenceType, $referenceId, $description);
            }
        }

        return $ledger;
    }

    /**
     * Delete ledger entries by reference details.
     */
    public function deleteEntriesByReference(string $referenceType, int $referenceId): void
    {
        Ledger::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    /**
     * Create ledger entries for party opening balances.
     */
    public function createOpeningBalanceEntries(Party $party): void
    {
        $openingBalance = round((float) ($party->opening_balance ?? 0), 2);

        if ($openingBalance <= 0) {
            return;
        }

        $partyAccountCode = $party->type === 'debtor' ? Account::CODE_AR : Account::CODE_AP;
        $partyAccount = $this->ensureSystemAccount(
            $partyAccountCode,
            $party->company_id,
            $party->financial_year_id,
            $party->created_by,
            $party->created_by_ip
        );

        $openingBalanceAccount = $this->ensureSystemAccount(
            Account::CODE_SUSPENSE,
            $party->company_id,
            $party->financial_year_id,
            $party->created_by,
            $party->created_by_ip
        );

        $partyDebit = 0;
        $partyCredit = 0;
        $offsetDebit = 0;
        $offsetCredit = 0;

        if ($party->opening_balance_type === 'debit') {
            $partyDebit = $openingBalance;
            $offsetCredit = $openingBalance;
        } else {
            $partyCredit = $openingBalance;
            $offsetDebit = $openingBalance;
        }

        $description = "Opening balance for party {$party->name}";
        $referenceType = 'party_opening_balance';
        $referenceId = $party->id;
        $transactionDate = $party->opening_date?->format('Y-m-d') ?? now()->format('Y-m-d');

        $this->createEntry(
            $party->company_id,
            $party->financial_year_id,
            $partyAccount->id,
            null,
            $transactionDate,
            $referenceType,
            $referenceId,
            $party->id,
            $description,
            $partyDebit,
            $partyCredit,
            $party->created_by,
            $party->created_by_ip
        );

        $this->createEntry(
            $party->company_id,
            $party->financial_year_id,
            $openingBalanceAccount->id,
            null,
            $transactionDate,
            $referenceType,
            $referenceId,
            $party->id,
            $description,
            $offsetDebit,
            $offsetCredit,
            $party->created_by,
            $party->created_by_ip
        );

        $this->recalculateBalances($partyAccount->id, $party->company_id);
        $this->recalculateBalances($openingBalanceAccount->id, $party->company_id);
    }

    protected function ensureSystemAccount(
        string $accountCode,
        int $companyId,
        ?int $financialYearId,
        ?int $createdBy,
        ?string $createdByIp
    ): Account {
        $account = $this->accountRepository->findByCode($accountCode, $companyId, $financialYearId);

        if ($account) {
            return $account;
        }

        $defaults = [
            Account::CODE_SUSPENSE => [
                'account_name' => 'Opening Balance Difference',
                'account_type' => 'asset',
                'balance_type' => 'debit',
                'remarks' => 'System suspense account for party opening balances.',
            ],
            Account::CODE_AR => [
                'account_name' => 'Accounts Receivable',
                'account_type' => 'asset',
                'balance_type' => 'debit',
                'remarks' => 'System account for customer receivables.',
            ],
            Account::CODE_AP => [
                'account_name' => 'Accounts Payable',
                'account_type' => 'liability',
                'balance_type' => 'credit',
                'remarks' => 'System account for vendor payables.',
            ],
        ];

        if (!isset($defaults[$accountCode])) {
            throw new \InvalidArgumentException("Unsupported system account code: {$accountCode}");
        }

        return Account::create(array_merge($defaults[$accountCode], [
            'uuid' => Str::uuid()->toString(),
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'account_code' => $accountCode,
            'opening_balance' => 0,
            'opening_date' => now(),
            'is_active' => true,
            'is_system' => true,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'created_by_ip' => $createdByIp,
            'updated_by_ip' => $createdByIp,
            'version' => 1,
        ]));
    }

    /**
     * Get ledger entries for an account
     */
    public function getAccountLedger(
        int $accountId,
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $query = Ledger::where('company_id', $companyId)
            ->where('account_id', $accountId)
            ->with(['voucher', 'account']);

        if ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        if ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }

        $entries = $query->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Get opening balance
        $openingBalance = $this->getOpeningBalance($accountId, $companyId, $financialYearId, $dateFrom);

        // Calculate totals
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        // Get closing balance
        $lastEntry = $entries->last();
        $closingBalance = $lastEntry ? $lastEntry->running_balance : $openingBalance['balance'];
        $closingBalanceType = $lastEntry ? $lastEntry->balance_type : $openingBalance['type'];

        return [
            'account' => $this->accountRepository->find($accountId),
            'opening_balance' => $openingBalance,
            'entries' => $entries,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => [
                'balance' => $closingBalance,
                'type' => $closingBalanceType,
            ],
        ];
    }

    /**
     * Get opening balance for an account
     */
    public function getOpeningBalance(
        int $accountId,
        int $companyId,
        ?int $financialYearId = null,
        ?string $asOfDate = null
    ): array {
        $account = $this->accountRepository->find($accountId);
        
        if (!$account) {
            return ['balance' => 0, 'type' => 'debit'];
        }

        $query = Ledger::where('company_id', $companyId)
            ->where('account_id', $accountId);

        if ($financialYearId) {
            $financialYear = FinancialYear::find($financialYearId);
            if ($financialYear && $asOfDate) {
                $query->where('transaction_date', '<', $asOfDate);
            }
        }

        $lastEntry = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastEntry) {
            return [
                'balance' => $lastEntry->running_balance,
                'type' => $lastEntry->balance_type,
            ];
        }

        // Return account opening balance if no entries
        return [
            'balance' => $account->opening_balance,
            'type' => $account->opening_balance >= 0 ? 'debit' : 'credit',
        ];
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance(int $companyId, int $financialYearId): array
    {
        $accounts = $this->accountRepository->getActiveByCompany($companyId);

        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $companyId, $financialYearId);
            
            if ($balance['balance'] > 0) {
                $trialBalance[] = [
                    'account' => $account,
                    'debit' => $balance['type'] === 'debit' ? $balance['balance'] : 0,
                    'credit' => $balance['type'] === 'credit' ? $balance['balance'] : 0,
                ];

                if ($balance['type'] === 'debit') {
                    $totalDebit += $balance['balance'];
                } else {
                    $totalCredit += $balance['balance'];
                }
            }
        }

        return [
            'accounts' => $trialBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(int $accountId, int $companyId, int $financialYearId): array
    {
        $account = $this->accountRepository->find($accountId);
        
        if (!$account) {
            return ['balance' => 0, 'type' => 'debit'];
        }

        $lastEntry = Ledger::where('company_id', $companyId)
            ->where('account_id', $accountId)
            ->where('financial_year_id', $financialYearId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastEntry) {
            return [
                'balance' => $lastEntry->running_balance,
                'type' => $lastEntry->balance_type,
            ];
        }

        return [
            'balance' => $account->opening_balance,
            'type' => $account->opening_balance >= 0 ? 'debit' : 'credit',
        ];
    }

    /**
     * Recalculate running balances for an account
     */
    public function recalculateBalances(int $accountId, int $companyId): void
    {
        $entries = Ledger::where('company_id', $companyId)
            ->where('account_id', $accountId)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $account = $this->accountRepository->find($accountId);
        $isDebitNormal = in_array($account->account_type, ['asset', 'expense']);
        $runningBalance = $account->opening_balance;
        $balanceType = $runningBalance >= 0 ? 'debit' : 'credit';

        foreach ($entries as $entry) {
            if ($isDebitNormal) {
                $runningBalance = $runningBalance + $entry->debit - $entry->credit;
            } else {
                $runningBalance = $runningBalance + $entry->credit - $entry->debit;
            }

            $balanceType = $runningBalance >= 0 ? 'debit' : 'credit';

            $entry->update([
                'running_balance' => abs($runningBalance),
                'balance_type' => $balanceType,
            ]);
        }
    }
}
