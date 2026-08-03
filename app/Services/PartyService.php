<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PartyService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Get all parties with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Party::with(['company', 'account']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('party_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('mobile', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * Get paginated parties
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Party::with(['company', 'account']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('party_code', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Get party by ID
     */
    public function getById(int $id): ?Party
    {
        return Party::with(['company', 'account', 'vouchers'])->find($id);
    }

    /**
     * Find the most recently deleted party with the same normalized name.
     */
    public function findDeletedByName(int $companyId, string $name): ?Party
    {
        return Party::onlyTrashed()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
            ->latest('deleted_at')
            ->first();
    }

    /**
     * Create party
     */
    public function create(array $data): Party
    {
        // Generate party code if not provided
        if (empty($data['party_code'])) {
            $data['party_code'] = Party::generateCode(
                $data['type'],
                $data['company_id']
            );
        }

        if (empty($data['opening_balance_type'])) {
            $data['opening_balance_type'] = isset($data['type']) && $data['type'] === 'creditor' ? 'credit' : 'debit';
        }

        if (!array_key_exists('address', $data)) {
            $data['address'] = '';
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

        try {
            DB::beginTransaction();

            if (empty($data['account_id'])) {
                $data['account_id'] = $this->resolveControlAccount($data)->id;
            }

            $party = Party::create($data);

            if (!empty($party->opening_balance) && (float) $party->opening_balance > 0) {
                $this->ledgerService->createOpeningBalanceEntries($party);
            }

            DB::commit();

            return $party;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore a deleted party and replace its details with the submitted data.
     */
    public function restoreDeleted(Party $party, array $data): Party
    {
        if (!$party->trashed()) {
            throw new \InvalidArgumentException('The selected party is not deleted.');
        }

        unset(
            $data['party_code'],
            $data['duplicate_action'],
            $data['created_by'],
            $data['created_by_ip']
        );

        if (empty($data['opening_balance_type'])) {
            $data['opening_balance_type'] = ($data['type'] ?? $party->type) === 'creditor'
                ? 'credit'
                : 'debit';
        }

        if (!array_key_exists('address', $data)) {
            $data['address'] = '';
        }

        $financialYear = FinancialYear::getCurrent($party->company_id);
        $data['financial_year_id'] = $financialYear?->id;
        if (empty($data['opening_date'])) {
            $data['opening_date'] = $this->resolveOpeningDate($party->company_id, $financialYear);
        }
        $data['company_id'] = $party->company_id;
        $data['deleted_by'] = null;
        $data['deleted_by_id'] = null;

        return DB::transaction(function () use ($party, $data) {
            $data['account_id'] = $this->resolveControlAccount([
                ...$data,
                'created_by' => $data['updated_by'] ?? $party->created_by,
                'created_by_ip' => $data['updated_by_ip'] ?? request()->ip(),
            ])->id;

            $party->restore();
            $party->update($data);

            if ((float) $party->opening_balance > 0) {
                $this->ledgerService->createOpeningBalanceEntries($party);
            }

            return $party->fresh();
        });
    }

    /**
     * Update party
     */
    public function update(int $id, array $data): bool
    {
        $party = Party::find($id);

        if (!$party) {
            return false;
        }

        // Opening balance is set only at create and cannot be changed later.
        unset(
            $data['opening_balance'],
            $data['opening_balance_type'],
            $data['opening_date']
        );

        // Debtor/Creditor reclassification is locked once any ledger activity exists.
        if ($this->isPartyTransactionallyUsed($party->id)) {
            unset($data['type']);
        }

        try {
            DB::beginTransaction();

            // Keep account_id pointing at the correct AR/AP control account.
            $data['account_id'] = $this->resolveControlAccount([
                'type' => $data['type'] ?? $party->type,
                'company_id' => $party->company_id,
                'financial_year_id' => $party->financial_year_id,
                'created_by' => $data['updated_by'] ?? $party->created_by,
                'created_by_ip' => $data['updated_by_ip'] ?? request()->ip(),
            ])->id;

            $updated = $party->update($data);

            DB::commit();

            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete party
     */
    public function delete(int $id): bool
    {
        $party = Party::find($id);

        if (!$party) {
            return false;
        }

        if ($this->hasPartyTransactions($party->id)) {
            throw new \Exception(
                'This party cannot be deleted because accounting transactions are linked to it. '
                . 'Mark the party inactive instead.'
            );
        }

        return DB::transaction(function () use ($party) {
            $this->ledgerService->deletePartyOpeningBalanceEntries($party);

            return $party->delete();
        });
    }

    /**
     * Get parties for dropdown
     */
    public function getForDropdown(int $companyId, ?string $type = null): array
    {
        $query = Party::where('company_id', $companyId)
            ->where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('name')
            ->get()
            ->map(function ($party) {
                return [
                    'id' => $party->id,
                    'text' => "{$party->party_code} - {$party->name}",
                    'type' => $party->type,
                ];
            })
            ->toArray();
    }

    /**
     * Build invoice party dropdown options with existing parties and
     * Cash/Bank/OD ledgers as selectable entries.
     */
    public function getInvoicePartyOptions(int $companyId, string $type): array
    {
        $partyOptions = Party::query()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Party $party) {
                return [
                    'value' => 'party:' . $party->id,
                    'label' => "{$party->name} ({$party->party_code})",
                    'kind' => 'party',
                ];
            })
            ->values()
            ->toArray();

        $ledgerOptions = Account::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->cashBankOd()
            ->orderBy('account_code')
            ->get()
            ->map(function (Account $account) {
                return [
                    'value' => 'account:' . $account->id,
                    'label' => "{$account->account_name} ({$account->account_code})",
                    'kind' => 'cash_bank_od',
                ];
            })
            ->values()
            ->toArray();

        return [
            'parties' => $partyOptions,
            'cash_bank_od_accounts' => $ledgerOptions,
        ];
    }

    /**
     * Resolve invoice party selection token to a concrete party ID.
     */
    public function resolveInvoicePartySelection(
        $selection,
        int $companyId,
        string $type,
        ?int $userId = null,
        ?string $ip = null
    ): int {
        $selection = trim((string) $selection);

        if (str_starts_with($selection, 'party:')) {
            $partyId = (int) substr($selection, 6);
            $party = Party::query()
                ->where('company_id', $companyId)
                ->where('type', $type)
                ->where('is_active', true)
                ->find($partyId);

            if (!$party) {
                throw new \RuntimeException('Selected party is invalid for this company.');
            }

            return (int) $party->id;
        }

        if (!str_starts_with($selection, 'account:')) {
            throw new \RuntimeException('Select a valid customer/supplier option.');
        }

        $accountId = (int) substr($selection, 8);
        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('id', $accountId)
            ->where('is_active', true)
            ->cashBankOd()
            ->first();

        if (!$account) {
            throw new \RuntimeException('Selected ledger is invalid for this invoice.');
        }

        $normalizedName = mb_strtolower(trim($account->account_name));

        $existingParty = Party::query()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->orderByDesc('id')
            ->first();

        if ($existingParty) {
            $existingParty->update([
                'account_id' => $account->id,
                'is_active' => true,
                'updated_by' => $userId,
                'updated_by_ip' => $ip,
            ]);

            return (int) $existingParty->id;
        }

        $deletedParty = Party::onlyTrashed()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->latest('deleted_at')
            ->first();

        if ($deletedParty) {
            $deletedParty->restore();
            $deletedParty->update([
                'account_id' => $account->id,
                'is_active' => true,
                'updated_by' => $userId,
                'updated_by_ip' => $ip,
                'deleted_by' => null,
                'deleted_by_id' => null,
            ]);

            return (int) $deletedParty->id;
        }

        throw new \RuntimeException(
            'Selected ledger is not mapped to any party. Please choose an existing party or create one first.'
        );
    }

    /**
     * Resolve invoice selector token into party/account ids for posting.
     *
     * - party token: returns that party and its mapped account
     * - account token: returns mapped party if found, else party_id=null with direct account_id
     *
     * @return array{party_id:int|null,account_id:int}
     */
    public function resolveInvoiceSelectionForPosting(
        $selection,
        int $companyId,
        string $type
    ): array {
        $selection = trim((string) $selection);

        if (ctype_digit($selection)) {
            $selection = 'party:' . $selection;
        }

        if (str_starts_with($selection, 'party:')) {
            $partyId = (int) substr($selection, 6);
            $party = Party::query()
                ->where('company_id', $companyId)
                ->where('type', $type)
                ->where('is_active', true)
                ->find($partyId);

            if (!$party) {
                throw new \RuntimeException('Selected party is invalid for this company.');
            }

            $accountId = (int) ($party->account_id ?: 0);
            if ($accountId <= 0) {
                $accountId = (int) $this->resolveControlAccount([
                    'type' => $type,
                    'company_id' => $companyId,
                    'financial_year_id' => FinancialYear::getCurrent($companyId)?->id,
                    'created_by' => request()->user()?->id,
                    'created_by_ip' => request()->ip(),
                ])->id;
            }

            return [
                'party_id' => (int) $party->id,
                'account_id' => $accountId,
            ];
        }

        if (!str_starts_with($selection, 'account:')) {
            throw new \RuntimeException('Select a valid customer/supplier option.');
        }

        $accountId = (int) substr($selection, 8);

        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('id', $accountId)
            ->where('is_active', true)
            ->cashBankOd()
            ->first();

        if (!$account) {
            throw new \RuntimeException('Selected ledger is invalid for this invoice.');
        }

        $mappedParty = Party::query()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->where('is_active', true)
            ->where('account_id', $account->id)
            ->orderByDesc('id')
            ->first();

        return [
            'party_id' => $mappedParty ? (int) $mappedParty->id : null,
            'account_id' => (int) $account->id,
        ];
    }

    /**
     * Get debtors outstanding
     */
    public function getDebtorsOutstanding(int $companyId): Collection
    {
        return Party::where('company_id', $companyId)
            ->where('type', 'debtor')
            ->where('is_active', true)
            ->withSum(['vouchers' => function ($query) {
                $query->where('status', 'posted');
            }], 'total_debit')
            ->withSum(['vouchers' => function ($query) {
                $query->where('status', 'posted');
            }], 'total_credit')
            ->get();
    }

    /**
     * Get creditors outstanding
     */
    public function getCreditorsOutstanding(int $companyId): Collection
    {
        return Party::where('company_id', $companyId)
            ->where('type', 'creditor')
            ->where('is_active', true)
            ->withSum(['vouchers' => function ($query) {
                $query->where('status', 'posted');
            }], 'total_debit')
            ->withSum(['vouchers' => function ($query) {
                $query->where('status', 'posted');
            }], 'total_credit')
            ->get();
    }

    /**
     * Resolve the shared AR/AP control account a party posts to.
     * Debtors roll into Accounts Receivable, creditors into Accounts Payable.
     */
    protected function resolveControlAccount(array $partyData): Account
    {
        $code = ($partyData['type'] ?? 'debtor') === 'creditor'
            ? Account::CODE_AP
            : Account::CODE_AR;

        return $this->ledgerService->ensureSystemAccount(
            $code,
            (int) $partyData['company_id'],
            $partyData['financial_year_id'] ?? null,
            $partyData['created_by'] ?? null,
            $partyData['created_by_ip'] ?? request()->ip()
        );
    }

    /**
     * Whether the party has any ledger rows (including opening-balance adjustments).
     */
    public function isTransactionallyUsed(int $partyId): bool
    {
        return $this->isPartyTransactionallyUsed($partyId);
    }

    /**
     * A party is transactionally used when any ledger row is tagged with it.
     */
    protected function isPartyTransactionallyUsed(int $partyId): bool
    {
        return Ledger::where('party_id', $partyId)->exists();
    }

    /**
     * Determine whether a party has activity other than its own opening balance.
     */
    protected function hasPartyTransactions(int $partyId): bool
    {
        $openingVoucherIds = DB::table('vouchers')
            ->where('voucher_type', 'adjustment')
            ->where('narration', 'like', "[OB:party:{$partyId}]%")
            ->pluck('id');

        return Ledger::where('party_id', $partyId)
            ->when(
                $openingVoucherIds->isNotEmpty(),
                fn ($query) => $query->where(fn ($q) => $q
                    ->whereNull('voucher_id')
                    ->orWhereNotIn('voucher_id', $openingVoucherIds))
            )
            ->exists();
    }

    protected function resolveOpeningDate(int $companyId, ?FinancialYear $financialYear = null): string
    {
        $financialYear ??= FinancialYear::getCurrent($companyId);

        return $financialYear?->start_date?->format('Y-m-d')
            ?? now()->toDateString();
    }
}
