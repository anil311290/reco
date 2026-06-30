<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AccountService
{
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
        try {
            DB::beginTransaction();

            // Generate account code if not provided
            if (empty($data['account_code'])) {
                $data['account_code'] = Account::generateCode(
                    $data['account_type'],
                    $data['company_id']
                );
            } elseif (Account::isReservedCode($data['account_code'])) {
                throw new \Exception(
                    "Account code {$data['account_code']} is reserved for system use: " .
                    Account::RESERVED_CODES[$data['account_code']]
                );
            }

            // Set opening date if not provided
            if (empty($data['opening_date'])) {
                $data['opening_date'] = now();
            }

            // Get current financial year if not provided
            if (empty($data['financial_year_id'])) {
                $financialYear = FinancialYear::getCurrent($data['company_id']);
                $data['financial_year_id'] = $financialYear?->id;
            }

            $account = Account::create($data);

            DB::commit();

            return $account;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

        // Prevent deleting system accounts
        if ($account->is_system) {
            throw new \Exception('Cannot delete system account.');
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
                ];
            })
            ->toArray();
    }
}
