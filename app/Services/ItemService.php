<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemService
{
    /**
     * Get all items for a company.
     */
    public function getAll(int $companyId, array $filters = []): Collection
    {
        $query = Item::with(['category', 'taxRate', 'incomeAccount', 'expenseAccount'])
            ->where('company_id', $companyId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('item_code', 'like', "%{$filters['search']}%")
                  ->orWhere('barcode', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get paginated items.
     */
    public function getPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Item::with(['category', 'taxRate'])
            ->where('company_id', $companyId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('item_code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get item by ID.
     */
    public function getById(int $id): ?Item
    {
        return Item::with(['category', 'taxRate', 'incomeAccount', 'expenseAccount'])->find($id);
    }

    /**
     * Create an item.
     */
    public function create(array $data): Item
    {
        $data['current_stock'] = $data['opening_stock'] ?? 0;
        return Item::create($data);
    }

    /**
     * Update an item.
     */
    public function update(int $id, array $data): bool
    {
        return Item::findOrFail($id)->update($data);
    }

    /**
     * Delete an item.
     */
    public function delete(int $id): bool
    {
        return Item::findOrFail($id)->delete();
    }

    /**
     * Get low stock items.
     */
    public function getLowStock(int $companyId): Collection
    {
        return Item::where('company_id', $companyId)->lowStock()->get();
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(int $id): Item
    {
        $item = Item::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return $item;
    }
}
