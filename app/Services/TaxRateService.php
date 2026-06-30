<?php

namespace App\Services;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaxRateService
{
    /**
     * Get all tax rates for a company.
     */
    public function getAll(int $companyId, bool $activeOnly = true): Collection
    {
        $query = TaxRate::where('company_id', $companyId);
        if ($activeOnly) {
            $query->active();
        }
        return $query->orderBy('tax_name')->get();
    }

    /**
     * Get paginated tax rates.
     */
    public function getPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TaxRate::where('company_id', $companyId);

        $taxType = $filters['tax_type'] ?? $filters['calculation_type'] ?? null;
        if ($taxType) {
            $query->where('tax_type', $taxType);
        }

        $taxCategory = $filters['tax_category'] ?? $filters['category'] ?? null;
        if ($taxCategory) {
            $query->where('tax_category', $taxCategory);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        } elseif (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('status', $filters['is_active'] ? 'active' : 'inactive');
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('tax_name', 'like', "%{$filters['search']}%")
                  ->orWhere('tax_code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('tax_name')->paginate($perPage);
    }

    /**
     * Get tax rate by ID.
     */
    public function getById(int $id): ?TaxRate
    {
        return TaxRate::find($id);
    }

    /**
     * Create a tax rate.
     */
    public function create(array $data): TaxRate
    {
        return TaxRate::create($data);
    }

    /**
     * Update a tax rate.
     */
    public function update(int $id, array $data): bool
    {
        return TaxRate::findOrFail($id)->update($data);
    }

    /**
     * Delete a tax rate.
     */
    public function delete(int $id): bool
    {
        return TaxRate::findOrFail($id)->delete();
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(int $id): TaxRate
    {
        $taxRate = TaxRate::findOrFail($id);
        $taxRate->update([
            'status' => $taxRate->status === 'active' ? 'inactive' : 'active',
        ]);
        return $taxRate;
    }
}
