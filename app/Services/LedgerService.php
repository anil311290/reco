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

            $previousEntries = Ledger::where('voucher_id', $voucher->id)
                ->get(['company_id', 'account_id']);

            // Delete existing ledger entries for this voucher
            $this->ledgerRepository->deleteByVoucher($voucher->id);

            $touchedAccountIds = $previousEntries->pluck('account_id');
            $partyByAccount = Party::where('company_id', $voucher->company_id)
                ->whereIn('account_id', $voucher->lines->pluck('account_id'))
                ->pluck('id', 'account_id');

            foreach ($voucher->lines as $line) {
                $touchedAccountIds->push($line->account_id);
                $linePartyId = $partyByAccount[$line->account_id] ?? $voucher->party_id;

                $this->createEntry(
                    $voucher->company_id,
                    $voucher->financial_year_id,
                    $line->account_id,
                    $voucher->id,
                    $voucher->voucher_date->format('Y-m-d'),
                    'voucher',
                    $voucher->id,
                    $linePartyId,
                    $line->description ?? $voucher->narration,
                    $line->debit,
                    $line->credit,
                    $voucher->created_by,
                    $voucher->created_by_ip
                );
            }

            foreach ($touchedAccountIds->unique() as $accountId) {
                $this->recalculateBalances(
                    (int) $accountId,
                    $voucher->company_id,
                    $voucher->financial_year_id ? (int) $voucher->financial_year_id : null
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
        // Calculate running balance within the financial year when provided.
        $lastEntry = $this->ledgerRepository->getLastEntry($companyId, $accountId, $financialYearId);

        $account = $this->accountRepository->find($accountId);
        $isDebitNormal = in_array($account->account_type, ['asset', 'expense'], true);

        if ($lastEntry) {
            $magnitude = (float) $lastEntry->running_balance;
            if ($isDebitNormal) {
                $previousBalance = $lastEntry->balance_type === 'debit' ? $magnitude : -$magnitude;
            } else {
                $previousBalance = $lastEntry->balance_type === 'credit' ? $magnitude : -$magnitude;
            }
        } else {
            // First entry in FY: use master opening only when no posted opening rows exist yet.
            $previousBalance = $financialYearId
                ? 0.0
                : (float) ($account->opening_balance ?? 0);

            if ($financialYearId && round((float) ($account->opening_balance ?? 0), 2) > 0) {
                $hasOpeningRows = Ledger::query()
                    ->where('company_id', $companyId)
                    ->where('account_id', $accountId)
                    ->where('financial_year_id', $financialYearId)
                    ->whereIn('reference_type', [
                        'account_opening_balance',
                        'party_opening_balance',
                        'fy_opening_balance',
                    ])
                    ->exists();

                if (!$hasOpeningRows) {
                    $previousBalance = (float) $account->opening_balance;
                }
            }
        }

        // Calculate new balance on account-normal scale
        if ($isDebitNormal) {
            $newBalance = $previousBalance + $debit - $credit;
        } else {
            $newBalance = $previousBalance + $credit - $debit;
        }

        // Positive normal-side balance keeps the account's normal Dr/Cr type
        if ($newBalance >= 0) {
            $balanceType = $isDebitNormal ? 'debit' : 'credit';
        } else {
            $balanceType = $isDebitNormal ? 'credit' : 'debit';
        }
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
        $entries = Ledger::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get(['company_id', 'account_id']);

        Ledger::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();

        foreach ($entries->groupBy('company_id') as $companyId => $companyEntries) {
            foreach ($companyEntries->pluck('account_id')->unique() as $accountId) {
                $this->recalculateBalances((int) $accountId, (int) $companyId);
            }
        }
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

        $partyAccount = $party->account_id
            ? Account::where('company_id', $party->company_id)->find($party->account_id)
            : null;

        if (!$partyAccount) {
            throw new \RuntimeException(
                "Party {$party->name} has no linked ledger account for its opening balance."
            );
        }

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

        $this->recalculateBalances($partyAccount->id, $party->company_id, $party->financial_year_id);
        $this->recalculateBalances($openingBalanceAccount->id, $party->company_id, $party->financial_year_id);
    }

    /**
     * Post a balanced opening for a ledger account (account ↔ suspense).
     * Keeps Trial Balance balanced when opening_balance is set.
     */
    public function createAccountOpeningBalanceEntries(Account $account): void
    {
        $openingBalance = round((float) ($account->opening_balance ?? 0), 2);
        if ($openingBalance <= 0) {
            return;
        }

        $existing = Ledger::query()
            ->where('company_id', $account->company_id)
            ->where('account_id', $account->id)
            ->where('reference_type', 'account_opening_balance')
            ->where('reference_id', $account->id)
            ->exists();

        if ($existing) {
            return;
        }

        $suspense = $this->ensureSystemAccount(
            Account::CODE_SUSPENSE,
            $account->company_id,
            $account->financial_year_id,
            $account->created_by,
            $account->created_by_ip
        );

        $balanceType = $account->balance_type
            ?: (in_array($account->account_type, ['asset', 'expense'], true) ? 'debit' : 'credit');

        $accountDebit = $balanceType === 'debit' ? $openingBalance : 0;
        $accountCredit = $balanceType === 'credit' ? $openingBalance : 0;
        $offsetDebit = $balanceType === 'credit' ? $openingBalance : 0;
        $offsetCredit = $balanceType === 'debit' ? $openingBalance : 0;

        $description = "Opening balance for {$account->account_name}";
        $transactionDate = $account->opening_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $referenceType = 'account_opening_balance';

        $this->createEntry(
            $account->company_id,
            $account->financial_year_id,
            $account->id,
            null,
            $transactionDate,
            $referenceType,
            $account->id,
            null,
            $description,
            $accountDebit,
            $accountCredit,
            $account->created_by,
            $account->created_by_ip
        );

        $this->createEntry(
            $account->company_id,
            $account->financial_year_id,
            $suspense->id,
            null,
            $transactionDate,
            $referenceType,
            $account->id,
            null,
            $description,
            $offsetDebit,
            $offsetCredit,
            $account->created_by,
            $account->created_by_ip
        );

        $this->recalculateBalances($account->id, $account->company_id, $account->financial_year_id);
        $this->recalculateBalances($suspense->id, $account->company_id, $account->financial_year_id);
    }

    public function ensureSystemAccount(
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
                'remarks' => 'System suspense account for opening balance differences.',
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
            Account::CODE_AR_INCOME => [
                'account_name' => 'Sales Revenue (AR)',
                'account_type' => 'income',
                'balance_type' => 'credit',
                'remarks' => 'Reserved default income account for AR transactions.',
            ],
            Account::CODE_AP_EXPENSE => [
                'account_name' => 'Purchases (AP)',
                'account_type' => 'expense',
                'balance_type' => 'debit',
                'remarks' => 'Reserved default expense account for AP transactions.',
            ],
        ];

        $meta = $defaults[$accountCode] ?? null;
        if (!$meta) {
            throw new \RuntimeException("Unknown system account code: {$accountCode}");
        }

        return Account::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'account_code' => $accountCode,
            'account_name' => $meta['account_name'],
            'account_type' => $meta['account_type'],
            'entry_source' => 'system',
            'opening_balance' => 0,
            'balance_type' => $meta['balance_type'],
            'remarks' => $meta['remarks'],
            'is_active' => true,
            'is_system' => true,
            'created_by' => $createdBy,
            'created_by_ip' => $createdByIp,
        ]);
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
     * Get company-wide ledger entries (all accounts).
     */
    public function getCompanyLedger(
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $query = Ledger::where('company_id', $companyId)
            ->with(['voucher', 'account', 'party']);

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

        return [
            'account' => null,
            'opening_balance' => ['balance' => 0, 'type' => 'debit'],
            'entries' => $entries,
            'total_debit' => $entries->sum('debit'),
            'total_credit' => $entries->sum('credit'),
            'closing_balance' => [
                'balance' => round($entries->sum('debit') - $entries->sum('credit'), 2),
                'type' => $entries->sum('debit') >= $entries->sum('credit') ? 'debit' : 'credit',
            ],
            'is_all_accounts' => true,
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
        $isDebitNormal = in_array($account->account_type, ['asset', 'expense'], true);
        $normalType = $account->balance_type
            ?: ($isDebitNormal ? 'debit' : 'credit');

        return [
            'balance' => abs((float) ($account->opening_balance ?? 0)),
            'type' => $normalType,
        ];
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance(int $companyId, int $financialYearId): array
    {
        // Inactive ledgers must remain visible when they carry historical
        // balances; deactivation only prevents new postings.
        $accounts = Account::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

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

        $isDebitNormal = in_array($account->account_type, ['asset', 'expense'], true);
        $normalType = $account->balance_type
            ?: ($isDebitNormal ? 'debit' : 'credit');

        return [
            'balance' => abs((float) $account->opening_balance),
            'type' => $normalType,
        ];
    }

    /**
     * Spendable balance for payment vouchers (Cash / Bank).
     * OD accounts return null (no limit). Credit balance on cash/bank = 0 available.
     */
    public function getAvailablePaymentBalance(int $accountId, int $companyId, int $financialYearId): ?float
    {
        $account = $this->accountRepository->find($accountId);

        if (!$account) {
            return 0.0;
        }

        if ($account->transaction_mode === 'od') {
            return null;
        }

        $balance = $this->getAccountBalance($accountId, $companyId, $financialYearId);

        if ($balance['type'] === 'debit') {
            return max(0.0, (float) $balance['balance']);
        }

        return 0.0;
    }

    /**
     * Recalculate running balances for an account
     */
    public function recalculateBalances(int $accountId, int $companyId, ?int $financialYearId = null): void
    {
        $query = Ledger::where('company_id', $companyId)
            ->where('account_id', $accountId);

        if ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        $entries = $query->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $account = $this->accountRepository->find($accountId);
        $isDebitNormal = in_array($account->account_type, ['asset', 'expense'], true);

        $hasPostedOpening = $entries->contains(function ($entry) {
            return in_array($entry->reference_type, [
                'account_opening_balance',
                'party_opening_balance',
                'fy_opening_balance',
            ], true);
        });

        if ($financialYearId) {
            $runningBalance = $hasPostedOpening
                ? 0.0
                : (float) ($account->opening_balance ?? 0);
        } else {
            $runningBalance = $hasPostedOpening
                ? 0.0
                : (float) ($account->opening_balance ?? 0);
        }

        $balanceType = $runningBalance >= 0
            ? ($isDebitNormal ? 'debit' : 'credit')
            : ($isDebitNormal ? 'credit' : 'debit');

        foreach ($entries as $entry) {
            if ($isDebitNormal) {
                $runningBalance = $runningBalance + $entry->debit - $entry->credit;
            } else {
                $runningBalance = $runningBalance + $entry->credit - $entry->debit;
            }

            $balanceType = $runningBalance >= 0
                ? ($isDebitNormal ? 'debit' : 'credit')
                : ($isDebitNormal ? 'credit' : 'debit');

            $entry->update([
                'running_balance' => abs($runningBalance),
                'balance_type' => $balanceType,
            ]);
        }
    }
}
