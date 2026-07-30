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
use Illuminate\Pagination\LengthAwarePaginator;
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

            foreach ($voucher->lines as $line) {
                $touchedAccountIds->push($line->account_id);
                $linePartyId = $line->party_id;

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
            // First ledger row starts at zero. Opening amounts are posted explicitly
            // via Adjustment vouchers (account ↔ Opening Balance Difference).
            $previousBalance = 0.0;
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
     * Create balanced opening for a party via Adjustment voucher
     * (party ledger ↔ Opening Balance Difference).
     */
    public function createOpeningBalanceEntries(Party $party): void
    {
        $this->removeOpeningAdjustment('party', (int) $party->id, (int) $party->company_id);
        $this->deleteEntriesByReference('party_opening_balance', (int) $party->id);

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

        $suspense = $this->ensureSystemAccount(
            Account::CODE_SUSPENSE,
            $party->company_id,
            $party->financial_year_id,
            $party->created_by,
            $party->created_by_ip
        );

        $isDebit = ($party->opening_balance_type ?? 'debit') === 'debit';
        $transactionDate = $party->opening_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $narration = $this->openingAdjustmentNarration('party', (int) $party->id, $party->name);

        $this->postOpeningAdjustmentVoucher(
            companyId: (int) $party->company_id,
            financialYearId: $party->financial_year_id ? (int) $party->financial_year_id : null,
            voucherDate: $transactionDate,
            narration: $narration,
            primaryAccountId: (int) $partyAccount->id,
            suspenseAccountId: (int) $suspense->id,
            amount: $openingBalance,
            primaryIsDebit: $isDebit,
            partyId: (int) $party->id,
            createdBy: $party->created_by,
            createdByIp: $party->created_by_ip
        );
    }

    public function deletePartyOpeningBalanceEntries(Party $party): void
    {
        $this->removeOpeningAdjustment('party', (int) $party->id, (int) $party->company_id);
        $this->deleteEntriesByReference('party_opening_balance', (int) $party->id);
    }

    /**
     * Create balanced opening for a ledger via Adjustment voucher
     * (account ↔ Opening Balance Difference) so Day Book / TB stay Dr = Cr.
     */
    public function createAccountOpeningBalanceEntries(Account $account): void
    {
        $this->removeOpeningAdjustment('account', (int) $account->id, (int) $account->company_id);
        $this->deleteEntriesByReference('account_opening_balance', (int) $account->id);

        $openingBalance = round((float) ($account->opening_balance ?? 0), 2);
        if ($openingBalance <= 0) {
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

        $transactionDate = $account->opening_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $narration = $this->openingAdjustmentNarration('account', (int) $account->id, $account->account_name);

        $this->postOpeningAdjustmentVoucher(
            companyId: (int) $account->company_id,
            financialYearId: $account->financial_year_id ? (int) $account->financial_year_id : null,
            voucherDate: $transactionDate,
            narration: $narration,
            primaryAccountId: (int) $account->id,
            suspenseAccountId: (int) $suspense->id,
            amount: $openingBalance,
            primaryIsDebit: $balanceType === 'debit',
            partyId: null,
            createdBy: $account->created_by,
            createdByIp: $account->created_by_ip
        );
    }

    public function deleteAccountOpeningBalanceEntries(Account $account): void
    {
        $this->removeOpeningAdjustment('account', (int) $account->id, (int) $account->company_id);
        $this->deleteEntriesByReference('account_opening_balance', (int) $account->id);
    }

    protected function openingAdjustmentNarration(string $kind, int $id, string $name): string
    {
        return sprintf('[OB:%s:%d] Opening balance for %s', $kind, $id, $name);
    }

    protected function removeOpeningAdjustment(string $kind, int $id, int $companyId): void
    {
        $marker = sprintf('[OB:%s:%d]', $kind, $id);

        $vouchers = Voucher::withTrashed()
            ->where('company_id', $companyId)
            ->where('voucher_type', 'adjustment')
            ->where('narration', 'like', $marker . '%')
            ->get();

        foreach ($vouchers as $voucher) {
            $this->deleteEntriesByReference('voucher', (int) $voucher->id);
            $voucher->lines()->delete();
            $voucher->forceDelete();
        }
    }

    protected function postOpeningAdjustmentVoucher(
        int $companyId,
        ?int $financialYearId,
        string $voucherDate,
        string $narration,
        int $primaryAccountId,
        int $suspenseAccountId,
        float $amount,
        bool $primaryIsDebit,
        ?int $partyId,
        ?int $createdBy,
        ?string $createdByIp
    ): void {
        $primaryLine = [
            'account_id' => $primaryAccountId,
            'party_id' => $partyId,
            'debit' => $primaryIsDebit ? $amount : 0,
            'credit' => $primaryIsDebit ? 0 : $amount,
            'description' => $narration,
        ];

        $offsetLine = [
            'account_id' => $suspenseAccountId,
            'party_id' => null,
            'debit' => $primaryIsDebit ? 0 : $amount,
            'credit' => $primaryIsDebit ? $amount : 0,
            'description' => $narration,
        ];

        app(VoucherService::class)->create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_type' => 'adjustment',
            'voucher_date' => $voucherDate,
            'narration' => $narration,
            'status' => 'posted',
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'created_by_ip' => $createdByIp,
            'updated_by_ip' => $createdByIp,
            'lines' => [$primaryLine, $offsetLine],
        ]);
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
                'remarks' => 'System suspense ledger used for balancing opening entries.',
            ],
            Account::CODE_PURCHASE_TAX => [
                'account_name' => 'Purchase Tax',
                'account_type' => 'asset',
                'balance_type' => 'debit',
                'remarks' => 'Default ledger for tax amount from purchase invoice lines.',
            ],
            Account::CODE_AR => [
                'account_name' => 'Accounts Receivable',
                'account_type' => 'asset',
                'balance_type' => 'debit',
                'remarks' => 'Control ledger for all debtor (customer) balances.',
            ],
            Account::CODE_SALES_TAX => [
                'account_name' => 'Sales Tax',
                'account_type' => 'liability',
                'balance_type' => 'credit',
                'remarks' => 'Default ledger for tax amount from sales invoice lines.',
            ],
            Account::CODE_AP => [
                'account_name' => 'Accounts Payable',
                'account_type' => 'liability',
                'balance_type' => 'credit',
                'remarks' => 'Control ledger for all creditor (supplier) balances.',
            ],
            Account::CODE_AR_INCOME => [
                'account_name' => 'Sales Revenue',
                'account_type' => 'income',
                'balance_type' => 'credit',
                'remarks' => 'Default income ledger for goods taxable totals on sales invoices.',
            ],
            Account::CODE_SERVICE_INCOME => [
                'account_name' => 'Service Revenue',
                'account_type' => 'income',
                'balance_type' => 'credit',
                'remarks' => 'Default income ledger for service taxable totals on sales invoices.',
            ],
            Account::CODE_AP_EXPENSE => [
                'account_name' => 'Purchase Expenses',
                'account_type' => 'expense',
                'balance_type' => 'debit',
                'remarks' => 'Default expense ledger for item totals on purchase invoices.',
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
     * Get ledger entries for an account (Tally ledger / cash / bank book source).
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

        $this->attachContraParticulars($entries);

        $openingBalance = $this->getOpeningBalance($accountId, $companyId, $financialYearId, $dateFrom);

        $totalDebit = round((float) $entries->sum('debit'), 2);
        $totalCredit = round((float) $entries->sum('credit'), 2);

        $lastEntry = $entries->last();
        if ($lastEntry) {
            $closingBalance = (float) $lastEntry->running_balance;
            $closingBalanceType = $lastEntry->balance_type;
        } else {
            $closingBalance = (float) $openingBalance['balance'];
            $closingBalanceType = $openingBalance['type'];
        }

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
     * Get a party's transaction history from the ledger, keyed by party_id.
     * Running balance is netted (debit − credit) so it is correct whether the
     * party posts to its own ledger account or to a shared AR/AP control account.
     */
    public function getPartyLedger(
        int $partyId,
        int $companyId,
        ?int $financialYearId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 0
    ): array {
        $query = Ledger::where('company_id', $companyId)
            ->where('party_id', $partyId)
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

        $running = 0.0;
        $rows = [];

        foreach ($entries as $entry) {
            $running += (float) $entry->debit - (float) $entry->credit;
            $rows[] = [
                'entry' => $entry,
                'running_balance' => abs(round($running, 2)),
                'running_type' => $running >= 0 ? 'debit' : 'credit',
            ];
        }

        $result = [
            'rows' => $rows,
            'total_debit' => round((float) $entries->sum('debit'), 2),
            'total_credit' => round((float) $entries->sum('credit'), 2),
            'closing_balance' => abs(round($running, 2)),
            'closing_type' => $running >= 0 ? 'debit' : 'credit',
            'paginator' => null,
        ];

        if ($perPage <= 0) {
            return $result;
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = count($rows);
        $pageItems = array_slice($rows, ($page - 1) * $perPage, $perPage);

        $result['rows'] = $pageItems;
        $result['paginator'] = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        return $result;
    }

    /**
     * Opening balance for a ledger report.
     *
     * - With $asOfDate: balance strictly before that date (period opening).
     * - Without $asOfDate in an FY: 0 — opening adjustments appear as first transactions
     *   (matches OB-via-Adjustment voucher flow; avoids using closing as opening).
     */
    public function getOpeningBalance(
        int $accountId,
        int $companyId,
        ?int $financialYearId = null,
        ?string $asOfDate = null
    ): array {
        $account = $this->accountRepository->find($accountId);

        if (!$account) {
            return ['balance' => 0.0, 'type' => 'debit'];
        }

        $isDebitNormal = in_array($account->account_type, ['asset', 'expense'], true);
        $defaultType = $account->balance_type
            ?: ($isDebitNormal ? 'debit' : 'credit');

        if ($asOfDate) {
            $query = Ledger::where('company_id', $companyId)
                ->where('account_id', $accountId)
                ->where('transaction_date', '<', $asOfDate);

            if ($financialYearId) {
                $query->where('financial_year_id', $financialYearId);
            }

            $lastEntry = $query->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($lastEntry) {
                return [
                    'balance' => round((float) $lastEntry->running_balance, 2),
                    'type' => $lastEntry->balance_type,
                ];
            }

            return ['balance' => 0.0, 'type' => $defaultType];
        }

        if ($financialYearId) {
            return ['balance' => 0.0, 'type' => $defaultType];
        }

        return [
            'balance' => abs(round((float) ($account->opening_balance ?? 0), 2)),
            'type' => $defaultType,
        ];
    }

    /**
     * Trial Balance (Tally / client Excel style):
     * Opening + period transactions + Closing, with BS/PL destination.
     * `debit` / `credit` remain closing balances for API/app backward compatibility.
     */
    public function getTrialBalance(int $companyId, int $financialYearId): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

        $periodSums = Ledger::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->selectRaw('account_id, COALESCE(SUM(debit), 0) as transaction_debit, COALESCE(SUM(credit), 0) as transaction_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $trialBalance = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $totalOpeningDebit = 0.0;
        $totalOpeningCredit = 0.0;
        $totalTransactionDebit = 0.0;
        $totalTransactionCredit = 0.0;

        foreach ($accounts as $account) {
            $closing = $this->getAccountBalance($account->id, $companyId, $financialYearId);
            $sumRow = $periodSums->get($account->id);

            $transactionDebit = round((float) ($sumRow->transaction_debit ?? 0), 2);
            $transactionCredit = round((float) ($sumRow->transaction_credit ?? 0), 2);

            $closingBalance = round((float) $closing['balance'], 2);
            $closingType = $closing['type'];
            $closingSigned = $closingType === 'debit' ? $closingBalance : -$closingBalance;
            $openingSigned = round($closingSigned - $transactionDebit + $transactionCredit, 2);
            $openingBalance = abs($openingSigned);
            $openingType = $openingSigned >= 0 ? 'debit' : 'credit';

            $openingDebit = $openingType === 'debit' ? $openingBalance : 0.0;
            $openingCredit = $openingType === 'credit' ? $openingBalance : 0.0;
            $closingDebit = $closingType === 'debit' ? $closingBalance : 0.0;
            $closingCredit = $closingType === 'credit' ? $closingBalance : 0.0;

            $hasMovement = $openingBalance > 0.001
                || $transactionDebit > 0.001
                || $transactionCredit > 0.001
                || $closingBalance > 0.001;

            if (!$hasMovement) {
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

        // Always replay from zero. Opening balances are ledger-posted
        // (Adjustment voucher / legacy opening refs), never seeded twice from master.
        $runningBalance = 0.0;

        $balanceType = $isDebitNormal ? 'debit' : 'credit';

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

    /**
     * Attach Tally-style particulars (contra ledger names) onto ledger rows.
     *
     * @param  \Illuminate\Support\Collection<int, Ledger>  $entries
     */
    protected function attachContraParticulars($entries): void
    {
        $voucherIds = $entries->pluck('voucher_id')->filter()->unique()->values();

        $linesByVoucher = $voucherIds->isEmpty()
            ? collect()
            : Ledger::query()
                ->whereIn('voucher_id', $voucherIds)
                ->with(['account:id,account_name,account_code'])
                ->get(['id', 'voucher_id', 'account_id'])
                ->groupBy('voucher_id');

        foreach ($entries as $entry) {
            $entry->setAttribute('particulars', $this->resolveContraParticulars($entry, $linesByVoucher));
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, \Illuminate\Support\Collection<int, Ledger>>  $linesByVoucher
     */
    protected function resolveContraParticulars(Ledger $entry, $linesByVoucher): string
    {
        if ($entry->voucher_id) {
            $siblings = ($linesByVoucher->get($entry->voucher_id) ?? collect())
                ->where('account_id', '!=', $entry->account_id);

            $names = $siblings
                ->map(static fn (Ledger $line) => $line->account?->account_name)
                ->filter()
                ->unique()
                ->values();

            if ($names->isNotEmpty()) {
                $label = $names->take(3)->implode(', ');
                if ($names->count() > 3) {
                    $label .= ' +' . ($names->count() - 3);
                }

                return $label;
            }
        }

        if ($entry->party?->name) {
            return $entry->party->name;
        }

        if (!empty($entry->description)) {
            return (string) $entry->description;
        }

        if (!empty($entry->voucher?->narration)) {
            return (string) $entry->voucher->narration;
        }

        return '-';
    }
}
