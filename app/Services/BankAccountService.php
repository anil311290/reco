<?php

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BankAccountService
{
    /**
     * Get all bank accounts for a company.
     */
    public function getAll(int $companyId, bool $activeOnly = true): Collection
    {
        $query = BankAccount::with('account')->where('company_id', $companyId);
        if ($activeOnly) {
            $query->active();
        }
        return $query->orderBy('is_default', 'desc')->orderBy('bank_name')->get();
    }

    /**
     * Get paginated bank accounts.
     */
    public function getPaginated(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return BankAccount::with('account')
            ->where('company_id', $companyId)
            ->orderBy('bank_name')
            ->paginate($perPage);
    }

    /**
     * Get bank account by ID.
     */
    public function getById(int $id): ?BankAccount
    {
        return BankAccount::with('account')->find($id);
    }

    /**
     * Create a bank account.
     */
    public function create(array $data): BankAccount
    {
        // If set as default, unset other defaults
        if (!empty($data['is_default'])) {
            BankAccount::where('company_id', $data['company_id'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return BankAccount::create($data);
    }

    /**
     * Update a bank account.
     */
    public function update(int $id, array $data): bool
    {
        $bankAccount = BankAccount::findOrFail($id);

        if (!empty($data['is_default'])) {
            BankAccount::where('company_id', $bankAccount->company_id)
                ->where('is_default', true)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        return $bankAccount->update($data);
    }

    /**
     * Delete a bank account.
     */
    public function delete(int $id): bool
    {
        return BankAccount::findOrFail($id)->delete();
    }

    /**
     * Get default bank account.
     */
    public function getDefault(int $companyId): ?BankAccount
    {
        return BankAccount::getDefault($companyId);
    }

    /**
     * Set default bank account.
     */
    public function setDefault(int $id, int $companyId): void
    {
        BankAccount::where('company_id', $companyId)
            ->update(['is_default' => false]);

        BankAccount::where('id', $id)->update(['is_default' => true]);
    }
}
