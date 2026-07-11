<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Party;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        return $query->orderBy('name')->get();
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

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get party by ID
     */
    public function getById(int $id): ?Party
    {
        return Party::with(['company', 'account', 'vouchers'])->find($id);
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

        // Set opening date if not provided
        if (empty($data['opening_date'])) {
            $data['opening_date'] = now();
        }

        if (!array_key_exists('address', $data)) {
            $data['address'] = '';
        }

        // Get current financial year if not provided
        if (empty($data['financial_year_id'])) {
            $financialYear = FinancialYear::getCurrent($data['company_id']);
            $data['financial_year_id'] = $financialYear?->id;
        }

        try {
            DB::beginTransaction();

            $linkedAccount = $this->createLinkedAccount($data);
            $data['account_id'] = $linkedAccount->id;

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
     * Update party
     */
    public function update(int $id, array $data): bool
    {
        $party = Party::find($id);

        if (!$party) {
            return false;
        }

        $oldOpeningBalance = $party->opening_balance;

        try {
            DB::beginTransaction();

            $this->syncLinkedAccount($party, $data);

            $updated = $party->update($data);

            if ($updated) {
                if (!empty($oldOpeningBalance) || !empty($data['opening_balance'])) {
                    $this->ledgerService->deleteEntriesByReference('party_opening_balance', $party->id);

                    if (!empty($party->opening_balance) && (float) $party->opening_balance > 0) {
                        $this->ledgerService->createOpeningBalanceEntries($party->fresh());
                    }
                }
            }

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

        if ($party->account_id) {
            Account::where('id', $party->account_id)->update(['is_active' => false]);
        }

        return $party->delete();
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
     * Get party-linked account options (AR/AP) for account-driven voucher forms.
     */
    public function getLinkedAccountDropdown(int $companyId, ?string $type = null): array
    {
        $query = Party::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->with('account');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('name')
            ->get()
            ->map(function (Party $party) {
                if (!$party->account) {
                    return null;
                }

                return [
                    'id' => $party->account->id,
                    'party_id' => $party->id,
                    'type' => $party->type,
                    'text' => "{$party->party_code} - {$party->name}",
                ];
            })
            ->filter()
            ->values()
            ->toArray();
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

    protected function createLinkedAccount(array $partyData): Account
    {
        $accountType = ($partyData['type'] ?? 'debtor') === 'creditor' ? 'liability' : 'asset';
        $balanceType = ($partyData['type'] ?? 'debtor') === 'creditor' ? 'credit' : 'debit';

        return Account::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $partyData['company_id'],
            'financial_year_id' => $partyData['financial_year_id'] ?? null,
            'account_code' => Account::generateCode($accountType, $partyData['company_id']),
            'account_name' => trim(($partyData['name'] ?? 'Party') . ' [' . ($partyData['party_code'] ?? 'PRTY') . ']'),
            'account_type' => $accountType,
            'entry_source' => 'system',
            'opening_balance' => (float) ($partyData['opening_balance'] ?? 0),
            'balance_type' => $balanceType,
            'opening_date' => $partyData['opening_date'] ?? now()->toDateString(),
            'remarks' => $partyData['remarks'] ?? 'Auto-linked account for party',
            'is_active' => (bool) ($partyData['is_active'] ?? true),
            'is_system' => false,
            'created_by' => $partyData['created_by'] ?? null,
            'updated_by' => $partyData['updated_by'] ?? null,
            'created_by_ip' => $partyData['created_by_ip'] ?? request()->ip(),
            'updated_by_ip' => $partyData['updated_by_ip'] ?? request()->ip(),
        ]);
    }

    protected function syncLinkedAccount(Party $party, array $partyData): void
    {
        $account = $party->account_id ? Account::find($party->account_id) : null;

        if (!$account) {
            $account = $this->createLinkedAccount([
                ...$party->toArray(),
                ...$partyData,
                'company_id' => $party->company_id,
                'party_code' => $party->party_code,
            ]);
            $partyData['account_id'] = $account->id;
            $party->account_id = $account->id;
        }

        $targetType = ($partyData['type'] ?? $party->type) === 'creditor' ? 'liability' : 'asset';
        $targetBalanceType = ($partyData['type'] ?? $party->type) === 'creditor' ? 'credit' : 'debit';

        $account->update([
            'account_name' => trim(($partyData['name'] ?? $party->name) . ' [' . ($party->party_code ?? 'PRTY') . ']'),
            'account_type' => $targetType,
            'balance_type' => $targetBalanceType,
            'opening_balance' => (float) ($partyData['opening_balance'] ?? $party->opening_balance ?? 0),
            'opening_date' => $partyData['opening_date'] ?? $party->opening_date ?? now()->toDateString(),
            'remarks' => $partyData['remarks'] ?? $party->remarks,
            'is_active' => (bool) ($partyData['is_active'] ?? $party->is_active),
            'updated_by' => $partyData['updated_by'] ?? null,
            'updated_by_ip' => $partyData['updated_by_ip'] ?? request()->ip(),
        ]);
    }
}
