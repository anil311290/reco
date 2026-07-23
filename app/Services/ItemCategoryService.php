<?php

namespace App\Services;

use App\Models\ItemCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemCategoryService
{
    /**
     * Get all item categories for a company.
     */
    public function getAll(int $companyId, bool $activeOnly = true): Collection
    {
        $query = ItemCategory::where('company_id', $companyId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * Get paginated item categories.
     */
    public function getPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ItemCategory::withCount('items')->where('company_id', $companyId);

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Get item category by ID.
     */
    public function getById(int $id): ?ItemCategory
    {
        return ItemCategory::find($id);
    }

    /**
     * Create item category.
     */
    public function create(array $data): ItemCategory
    {
        return ItemCategory::create($data);
    }

    /**
     * Update item category.
     */
    public function update(int $id, array $data): bool
    {
        return ItemCategory::findOrFail($id)->update($data);
    }

    /**
     * Delete item category.
     */
    public function delete(int $id): bool
    {
        return ItemCategory::findOrFail($id)->delete();
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(int $id): ItemCategory
    {
        $itemCategory = ItemCategory::findOrFail($id);
        $itemCategory->update([
            'is_active' => !$itemCategory->is_active,
        ]);

        return $itemCategory;
    }
}
