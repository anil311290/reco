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

            $data['account_id'] = $this->resolveControlAccount($data)->id;

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

        if ($this->isPartyTransactionallyUsed($party->id)) {
            throw new \Exception(
                'This party cannot be deleted because accounting transactions are linked to it. '
                . 'Mark the party inactive instead.'
            );
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
}
