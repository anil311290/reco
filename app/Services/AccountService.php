<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\Setting;
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

        return $query->orderBy('id', 'desc')->get();
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

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Get account by ID
     */
    public function getById(int $id): ?Account
    {
        return Account::with(['financialYear'])->find($id);
    }

    /**
     * Find the most recently deleted account with the same normalized name and type.
     */
    public function findDeletedByNameAndType(
        int $companyId,
        string $name,
        string $type
    ): ?Account {
        return Account::onlyTrashed()
            ->where('company_id', $companyId)
            ->where('account_type', $type)
            ->whereRaw('LOWER(TRIM(account_name)) = ?', [mb_strtolower(trim($name))])
            ->latest('deleted_at')
            ->first();
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
                    $data = $this->normalizeCashBankModePayload($data);

                    // Always generate a fresh company-scoped code on server.
                    $data['account_code'] = Account::generateCode(
                        $data['account_type'],
                        $data['company_id']
                    );
                    $data['entry_source'] = 'manual';

                    if (empty($data['balance_type'])) {
                        $data['balance_type'] = in_array($data['account_type'] ?? '', ['asset', 'expense'], true)
                            ? 'debit'
                            : 'credit';
                    }

                    // Get current financial year if not provided
                    $financialYear = null;
                    if (empty($data['financial_year_id'])) {
                        $financialYear = FinancialYear::getCurrent($data['company_id']);
                        $data['financial_year_id'] = $financialYear?->id;
                    } else {
                        $financialYear = FinancialYear::query()
                            ->where('company_id', $data['company_id'])
                            ->whereKey((int) $data['financial_year_id'])
                            ->first();
                    }

                    // Opening date defaults to current FY start date.
                    if (empty($data['opening_date'])) {
                        $data['opening_date'] = $this->resolveOpeningDate(
                            (int) $data['company_id'],
                            $financialYear
                        );
                    }

                    $account = Account::create($data);

                    if (round((float) ($account->opening_balance ?? 0), 2) > 0) {
                        app(LedgerService::class)->createAccountOpeningBalanceEntries($account);
                    }

                    return $account->fresh();
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

    /**
     * Restore a deleted account and replace its details with the submitted data.
     */
    public function restoreDeleted(Account $account, array $data): Account
    {
        if (!$account->trashed()) {
            throw new \InvalidArgumentException('The selected account is not deleted.');
        }

        unset(
            $data['account_code'],
            $data['duplicate_action'],
            $data['created_by'],
            $data['created_by_ip']
        );

        $data = $this->normalizeCashBankModePayload($data, $account->account_type);

        if (empty($data['balance_type'])) {
            $data['balance_type'] = in_array(
                $data['account_type'] ?? $account->account_type,
                ['asset', 'expense'],
                true
            ) ? 'debit' : 'credit';
        }

        $data['company_id'] = $account->company_id;
        $financialYear = FinancialYear::getCurrent($account->company_id);
        $data['financial_year_id'] = $financialYear?->id;
        if (empty($data['opening_date'])) {
            $data['opening_date'] = $this->resolveOpeningDate($account->company_id, $financialYear);
        }
        $data['entry_source'] = 'manual';
        $data['is_system'] = false;
        $data['deleted_by'] = null;
        $data['deleted_by_id'] = null;

        return DB::transaction(function () use ($account, $data) {
            $account->restore();
            $account->update($data);

            if (round((float) ($account->opening_balance ?? 0), 2) > 0) {
                app(LedgerService::class)->createAccountOpeningBalanceEntries($account);
            }

            return $account->fresh();
        });
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

    protected function resolveOpeningDate(int $companyId, ?FinancialYear $financialYear = null): string
    {
        $financialYear ??= FinancialYear::getCurrent($companyId);

        return $financialYear?->start_date?->format('Y-m-d')
            ?? now()->toDateString();
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

        unset(
            $data['opening_balance'],
            $data['balance_type'],
            $data['opening_date']
        );

        $isInUse = $this->isAccountInUse($account->id);

        // Renaming a ledger is safe: transactions reference its immutable ID.
        // Reclassifying a ledger after posting would rewrite historical reports.
        if ($isInUse) {
            unset(
                $data['account_type'],
                $data['is_cash_bank_od']
            );
        }

        if (
            array_key_exists('is_cash_bank_od', $data)
            || array_key_exists('account_type', $data)
        ) {
            $data = $this->normalizeCashBankModePayload($data, $account->account_type);
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

        if ($this->hasAccountTransactions($account->id)) {
            throw new \Exception(
                'This ledger cannot be deleted because transactions are linked to it. '
                . 'You may rename it or mark it inactive instead.'
            );
        }

        return DB::transaction(function () use ($account) {
            app(LedgerService::class)->deleteAccountOpeningBalanceEntries($account);

            DB::table('items')
                ->where('income_account_id', $account->id)
                ->update(['income_account_id' => null]);
            DB::table('items')
                ->where('expense_account_id', $account->id)
                ->update(['expense_account_id' => null]);
            DB::table('parties')
                ->where('account_id', $account->id)
                ->update(['account_id' => null]);
            DB::table('settings')
                ->whereIn('key', [
                    'sales_tax_ledger_id',
                    'purchase_tax_ledger_id',
                    'tds_ledger_id',
                    'tcs_ledger_id',
                    'cess_ledger_id',
                ])
                ->where('value', (string) $account->id)
                ->update(['value' => null]);

            return $account->delete();
        });
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
            ->where('is_active', true)
            ->where('account_code', '!=', Account::CODE_SUSPENSE);

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
                    'is_cash_bank_od' => (bool) $account->is_cash_bank_od,
                ];
            })
            ->toArray();
    }

    /**
     * Cash / Bank / OD accounts for payment/receipt header.
     */
    public function getCashBankAccountsForMode(
        int $companyId,
        ?int $financialYearId = null
    ): array {
        $query = Account::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('account_code', '!=', Account::CODE_SUSPENSE)
            ->cashBankOd();

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
                    'is_cash_bank_od' => (bool) $account->is_cash_bank_od,
                    'available_balance' => $available,
                ];
            })
            ->toArray();
    }

    /**
     * Particulars for payment/receipt lines: both Parties and Ledger Accounts.
     * Cash/Bank/OD are selected separately in Paid From / Received In, so they
     * are excluded here. Payment lists creditor parties first; Receipt lists
     * debtor parties first. Any other ledger account can also be selected.
     */
    public function getPaymentParticularsOptions(int $companyId, ?string $voucherType = null): array
    {
        // Both debtors and creditors are offered (like adjustments): a payment
        // can settle a creditor or refund a debtor, and a receipt can collect
        // from a debtor or a refund from a creditor. Cash/Bank/OD accounts are
        // excluded because they are the fixed contra side chosen separately.
        $parties = Party::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->buildParticularsOptions(
            $companyId,
            $parties,
            ['cash', 'bank', 'od']
        );
    }

    /**
     * Particulars for journal/adjustment vouchers: parties plus every ledger
     * account (cash/bank included). AR/AP control accounts are reached via the
     * party rows, so they are hidden from the direct account list.
     */
    public function getAdjustmentParticularsOptions(int $companyId): array
    {
        $parties = Party::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->buildParticularsOptions($companyId, $parties, []);
    }

    /**
     * Build a grouped particulars list: parties encoded as "party:{id}" tokens
     * (they post to a shared AR/AP control account) plus selectable ledger
     * accounts. AR/AP control accounts are always excluded from the direct list.
     *
     * @param \Illuminate\Support\Collection<int, Party> $parties
     * @param array<int, string> $excludedTransactionModes
     */
    protected function buildParticularsOptions(int $companyId, $parties, array $excludedTransactionModes): array
    {
        $options = collect();

        $partyBalances = collect();
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        if ($financialYearId && $parties->isNotEmpty()) {
            $partyIds = $parties->pluck('id')->all();

            $partyBalances = Ledger::query()
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereIn('party_id', $partyIds)
                ->selectRaw('party_id, COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
                ->groupBy('party_id')
                ->get()
                ->keyBy('party_id');
        }

        foreach ($parties as $party) {
            $balanceRow = $partyBalances->get($party->id);
            $debit = (float) ($balanceRow->debit_total ?? 0);
            $credit = (float) ($balanceRow->credit_total ?? 0);
            $signedBalance = round($debit - $credit, 2);
            $balanceAmount = abs($signedBalance);
            $balanceType = $signedBalance >= 0 ? 'debit' : 'credit';
            $balanceSuffix = $balanceAmount >= 0.01
                ? ' | Bal: ₹' . number_format($balanceAmount, 2) . ' ' . ($balanceType === 'debit' ? 'Dr' : 'Cr')
                : ' | Bal: ₹0.00';

            $options->push([
                'id' => 'party:' . $party->id,
                'party_id' => $party->id,
                'text' => "{$party->party_code} - {$party->name}{$balanceSuffix}",
                'kind' => 'party',
                'group' => 'Parties',
                'sort_order' => 10,
                'party_balance' => round($balanceAmount, 2),
                'party_balance_type' => $balanceType,
            ]);
        }

        Account::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotIn('account_code', [Account::CODE_SUSPENSE, Account::CODE_AR, Account::CODE_AP])
            ->when(
                $excludedTransactionModes !== [],
                // Cash / Bank / OD contra ledgers are controlled by the boolean
                // switch and should not appear in line-level particulars.
                fn ($query) => $query->where('is_cash_bank_od', false)
            )
            ->orderBy('account_code')
            ->get()
            ->each(function (Account $account) use ($options) {
                $options->push([
                    'id' => (string) $account->id,
                    'party_id' => null,
                    'text' => "{$account->account_code} - {$account->account_name}",
                    'kind' => 'account',
                    'group' => 'Ledger Accounts',
                    'sort_order' => 20,
                ]);
            });

        return $options
            ->sortBy(fn (array $option) => sprintf(
                '%02d|%s',
                $option['sort_order'],
                mb_strtolower($option['text'])
            ))
            ->values()
            ->toArray();
    }

    /**
     * Normalize cash/bank toggle payload.
     */
    protected function normalizeCashBankModePayload(array $data, ?string $fallbackType = null): array
    {
        unset($data['transaction_mode']);

        $accountType = $data['account_type'] ?? $fallbackType;

        if ($accountType !== 'asset') {
            $data['is_cash_bank_od'] = 0;

            return $data;
        }

        $data['is_cash_bank_od'] = !empty($data['is_cash_bank_od']) ? 1 : 0;

        return $data;
    }

    /**
     * @deprecated Prefer getCashBankAccountsForMode + getPaymentParticularsOptions
     */
    public function getCombinedPaymentDropdownOptions(int $companyId, ?string $voucherType = null): array
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
                'account_name' => 'Opening Balance',
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
                    'is_cash_bank_od' => false,
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
                'is_cash_bank_od' => false,
                'balance_type' => $meta['balance_type'],
                'remarks' => $meta['remarks'],
                'is_system' => true,
                'financial_year_id' => $account->financial_year_id ?: $financialYearId,
            ]);
        }

        $this->syncDefaultTaxLedgerSettings($companyId);

        return $created;
    }

    protected function syncDefaultTaxLedgerSettings(int $companyId): void
    {
        $salesTax = Account::where('company_id', $companyId)
            ->where('account_code', Account::CODE_SALES_TAX)
            ->first();
        $purchaseTax = Account::where('company_id', $companyId)
            ->where('account_code', Account::CODE_PURCHASE_TAX)
            ->first();

        if ($salesTax) {
            Setting::setValue(
                'sales_tax_ledger_id',
                (string) $salesTax->id,
                $companyId,
                'accounting'
            );
        }

        if ($purchaseTax) {
            Setting::setValue(
                'purchase_tax_ledger_id',
                (string) $purchaseTax->id,
                $companyId,
                'accounting'
            );
        }
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

    public function isAccountInUse(int $accountId): bool
    {
        if ($this->isAccountTransactionallyUsed($accountId)) {
            return true;
        }

        if (DB::table('sales_invoice_lines')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('purchase_invoice_lines')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('items')->where('income_account_id', $accountId)->orWhere('expense_account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('parties')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('settings')
            ->whereIn('key', [
                'sales_tax_ledger_id',
                'purchase_tax_ledger_id',
                'tds_ledger_id',
                'tcs_ledger_id',
                'cess_ledger_id',
            ])
            ->where('value', (string) $accountId)
            ->exists()) {
            return true;
        }

        return false;
    }

    public function isAccountTransactionallyUsed(int $accountId): bool
    {
        if (DB::table('ledgers')->where('account_id', $accountId)->exists()) {
            return true;
        }

        if (DB::table('voucher_lines')->where('account_id', $accountId)->exists()) {
            return true;
        }

        return false;
    }

    protected function hasAccountTransactions(int $accountId): bool
    {
        $openingVoucherIds = DB::table('vouchers')
            ->where('voucher_type', 'adjustment')
            ->where('narration', 'like', "[OB:account:{$accountId}]%")
            ->pluck('id');

        $hasLedgerEntries = DB::table('ledgers')
            ->where('account_id', $accountId)
            ->when(
                $openingVoucherIds->isNotEmpty(),
                fn ($query) => $query->where(fn ($q) => $q
                    ->whereNull('voucher_id')
                    ->orWhereNotIn('voucher_id', $openingVoucherIds))
            )
            ->exists();

        if ($hasLedgerEntries) {
            return true;
        }

        $hasVoucherLines = DB::table('voucher_lines')
            ->where('account_id', $accountId)
            ->when(
                $openingVoucherIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('voucher_id', $openingVoucherIds)
            )
            ->exists();

        if ($hasVoucherLines) {
            return true;
        }

        return DB::table('sales_invoice_lines')->where('account_id', $accountId)->exists()
            || DB::table('purchase_invoice_lines')->where('account_id', $accountId)->exists();
    }
}
