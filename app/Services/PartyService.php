<?php

namespace App\Services;

use App\Models\FinancialYear;
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
        $query = Party::with(['company']);

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
        $query = Party::with(['company']);

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
        return Party::with(['company', 'vouchers'])->find($id);
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

        // TODO: Check if party has vouchers

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
}
