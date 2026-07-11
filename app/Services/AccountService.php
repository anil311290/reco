<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Party;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountService
{
    /**
     * Ensure required system ledgers exist and remove safe duplicates.
     */
    public function ensureDefaultLedgersAndCleanupDuplicates(
        int $companyId,
        ?int $financialYearId = null,
        ?int $userId = null,
        ?string $ip = null
    ): array {
        $financialYearId ??= FinancialYear::getCurrent($companyId)?->id;
        $userId ??= request()->user()?->id;
        $ip ??= request()?->ip();

        $createdDefaults = $this->ensureRequiredSystemAccounts($companyId, $financialYearId, $userId, $ip);
        $removedDuplicates = $this->cleanupDuplicateAccounts($companyId);

        return [
            'created_defaults' => $createdDefaults,
            'removed_duplicates' => $removedDuplicates,
        ];
    }

    /**
     * Get all accounts with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Account::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('account_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('account_code', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('account_code')->get();
    }

    /**
     * Get paginated accounts
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Account::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('account_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('account_code', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('account_code')->paginate($perPage);
    }

    /**
     * Get account by ID
     */
    public function getById(int $id): ?Account
    {
        return Account::with(['financialYear'])->find($id);
    }

    /**
     * Create account
     */
    public function create(array $data): Account
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    if (($data['account_type'] ?? null) !== 'asset') {
                        $data['transaction_mode'] = null;
                    }

                    // Always generate a fresh company-scoped code on server.
                    $data['account_code'] = Account::generateCode(
                        $data['account_type'],
                        $data['company_id']
                    );
                    $data['entry_source'] = 'manual';

                    // Set opening date if not provided
                    if (empty($data['opening_date'])) {
                        $data['opening_date'] = now();
                    }

                    // Get current financial year if not provided
                    if (empty($data['financial_year_id'])) {
                        $financialYear = FinancialYear::getCurrent($data['company_id']);
                        $data['financial_year_id'] = $financialYear?->id;
                    }

                    return Account::create($data);
                });
            } catch (QueryException $e) {
                if ($attempt < $maxAttempts && $this->isDuplicateAccountCodeException($e)) {
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Unable to generate a unique account code. Please try again.');
    }

    protected function isDuplicateAccountCodeException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;
        $sqlState = $errorInfo[0] ?? null;
        $driverCode = (int) ($errorInfo[1] ?? 0);
        $message = $errorInfo[2] ?? $exception->getMessage();

        return $sqlState === '23000'
            && $driverCode === 1062
            && str_contains($message, 'accounts_company_account_code_unique');
    }

    /**
     * Update account
     */
    public function update(int $id, array $data): bool
    {
        $account = Account::find($id);

        if (!$account) {
            return false;
        }

        // Account name must remain immutable from edit flow.
        unset($data['account_name']);

        if (($data['account_type'] ?? $account->account_type) !== 'asset') {
            $data['transaction_mode'] = null;
        }

        // Prevent updating system accounts type
        if ($account->is_system && isset($data['account_type'])) {
            unset($data['account_type']);
        }

        return $account->update($data);
    }

    /**
     * Delete account
     */
    public function delete(int $id): bool
    {
        $account = Account::find($id);

        if (!$account) {
            return false;
        }

        // Allow delete only for manually created accounts.
        if (($account->entry_source ?? 'manual') !== 'manual' || $account->is_system) {
            throw new \Exception('Only manually created accounts can be deleted.');
        }

        // Check if account has children
        if ($account->hasChildren()) {
            throw new \Exception('Cannot delete account with sub-accounts.');
        }

        // TODO: Check if account has transactions

        return $account->delete();
    }

    /**
     * Get accounts grouped by type
     */
    public function getGrouped(int $companyId): array
    {
        return Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get()
            ->groupBy('account_type')
            ->toArray();
    }

    /**
     * Get account tree structure
     */
    public function getTree(int $companyId, ?string $type = null): Collection
    {
        $query = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('parent_id');

        if ($type) {
            $query->where('account_type', $type);
        }

        return $query->with('children.children.children')
            ->orderBy('account_code')
            ->get();
    }

    /**
     * Get accounts for dropdown
     */
    public function getForDropdown(int $companyId, ?string $type = null): array
    {
        $query = Account::where('company_id', $companyId)
            ->where('is_active', true);

        if ($type) {
            $query->where('account_type', $type);
        }

        return $query->orderBy('account_code')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'text' => "{$account->account_code} - {$account->account_name}",
                    'type' => $account->account_type,
                    'transaction_mode' => $account->transaction_mode,
                ];
            })
            ->toArray();
    }

    /**
     * Cash / Bank / OD accounts for payment/receipt header (by payment mode).
     */
    public function getCashBankAccountsForMode(
        int $companyId,
        ?string $transactionMode = null,
        ?int $financialYearId = null
    ): array {
        $query = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('transaction_mode', ['cash', 'bank', 'od']);

        if ($transactionMode) {
            $query->where('transaction_mode', $transactionMode);
        }

        $financialYearId ??= FinancialYear::getCurrent($companyId)?->id ?? 0;
        $ledgerService = app(LedgerService::class);

        return $query->orderBy('account_code')
            ->get()
            ->map(function (Account $account) use ($ledgerService, $companyId, $financialYearId) {
                $available = $ledgerService->getAvailablePaymentBalance(
                    $account->id,
                    $companyId,
                    $financialYearId
                );

                $balanceHint = $available === null
                    ? ''
                    : ' (Avail: ₹' . number_format($available, 2) . ')';

                return [
                    'id' => $account->id,
                    'text' => "{$account->account_code} - {$account->account_name}{$balanceHint}",
                    'transaction_mode' => $account->transaction_mode,
                    'available_balance' => $available,
                ];
            })
            ->toArray();
    }

    /**
     * Particulars for payment/receipt lines: parties + non-cash/bank ledgers.
     * Payment = creditors / expense-ish; Receipt = debtors / income-ish + all non cash accounts.
     */
    public function getPaymentParticularsOptions(int $companyId, ?string $voucherType = null): array
    {
        $options = collect();

        // Ledger accounts excluding cash/bank/od (those go in header)
        $accountQuery = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('transaction_mode')
                    ->orWhereNotIn('transaction_mode', ['cash', 'bank', 'od']);
            });

        $accountQuery->orderBy('account_code')->get()->each(function (Account $account) use ($options) {
            $options->put('a-' . $account->id, [
                'id' => $account->id,
                'text' => "A | {$account->account_code} - {$account->account_name}",
                'kind' => 'account',
            ]);
        });

        $partyQuery = Party::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->with('account')
            ->orderBy('name');

        if ($voucherType === 'receipt') {
            $partyQuery->where('type', 'debtor');
        } elseif ($voucherType === 'payment') {
            $partyQuery->where('type', 'creditor');
        }

        $partyQuery->get()->each(function (Party $party) use ($options) {
            if (!$party->account) {
                return;
            }

            $options->put('p-' . $party->account->id, [
                'id' => $party->account->id,
                'text' => "P | {$party->party_code} - {$party->name}",
                'kind' => 'party',
            ]);
        });

        return $options->values()->sortBy('text')->values()->toArray();
    }

    /**
     * @deprecated Prefer getCashBankAccountsForMode + getPaymentParticularsOptions
     */
    public function getCombinedPaymentDropdownOptions(int $companyId, ?string $transactionMode = null, ?string $voucherType = null): array
    {
        return $this->getPaymentParticularsOptions($companyId, $voucherType);
    }

    protected function ensureRequiredSystemAccounts(
        int $companyId,
        ?int $financialYearId,
        ?int $userId,
        ?string $ip
    ): int {
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

        $created = 0;

        foreach ($defaults as $code => $meta) {
            $account = Account::withTrashed()
                ->where('company_id', $companyId)
                ->where('account_code', $code)
                ->first();

            if (!$account) {
                Account::create([
                    'uuid' => Str::uuid()->toString(),
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'account_code' => $code,
                    'account_name' => $meta['account_name'],
                    'account_type' => $meta['account_type'],
                    'entry_source' => 'system',
                    'opening_balance' => 0,
                    'balance_type' => $meta['balance_type'],
                    'opening_date' => now()->toDateString(),
                    'remarks' => $meta['remarks'],
                    'is_active' => true,
                    'is_system' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_by_ip' => $ip,
                    'updated_by_ip' => $ip,
                ]);

                $created++;
                continue;
            }

            if ($account->trashed()) {
                $account->restore();
            }

            $account->update([
                'account_name' => $meta['account_name'],
                'account_type' => $meta['account_type'],
                'entry_source' => 'system',
                'balance_type' => $meta['balance_type'],
                'remarks' => $meta['remarks'],
                'is_system' => true,
                'is_active' => true,
                'financial_year_id' => $account->financial_year_id ?: $financialYearId,
            ]);
        }

        return $created;
    }

    protected function cleanupDuplicateAccounts(int $companyId): int
    {
        $accounts = Account::where('company_id', $companyId)
            ->orderByDesc('is_system')
            ->orderBy('id')
            ->get();

        $removed = 0;
        $groups = $accounts->groupBy(function (Account $account) {
            return strtolower(trim($account->account_name)) . '|' . $account->account_type;
        });

        foreach ($groups as $group) {
            if ($group->count() <= 1) {
                continue;
            }

            $keep = $group->first();
            foreach ($group as $account) {
                if ($account->id === $keep->id) {
                    continue;
                }

                if ($this->isAccountInUse($account->id)) {
                    continue;
                }

                $account->delete();
                $removed++;
            }
        }

        return $removed;
    }

    protected function isAccountInUse(int $accountId): bool
    {
        if (DB::table('ledgers')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('voucher_lines')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('sales_invoice_lines')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('purchase_invoice_lines')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('bank_accounts')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('items')->where('income_account_id', $accountId)->orWhere('expense_account_id', $accountId)->exists()) {
            return true;
        }

        return false;
    }
}
