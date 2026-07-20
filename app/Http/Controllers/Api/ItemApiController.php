<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Services\ItemService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemApiController extends Controller
{
    protected ItemService $itemService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
    }

    /**
     * Get all items.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $filters = $request->only(['search', 'type', 'category_id', 'is_active']);

        $items = $this->itemService->getAll($companyId, $filters);

        return ResponseHelper::success(ItemResource::collection($items));
    }

    /**
     * Get item by ID.
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        return ResponseHelper::success(new ItemResource($item));
    }

    /**
     * Item sales history (sales invoices only — no stock tracking).
     */
    public function history(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        $history = $this->itemService->getItemHistory(
            $id,
            $item->company_id,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
            (int) ($validated['per_page'] ?? 15)
        );

        $paginator = $history['paginator'];

        return ResponseHelper::success([
            'item' => new ItemResource($item),
            'total_in' => $history['total_in'],
            'total_out' => $history['total_out'],
            'total_sales_amount' => $history['total_sales_amount'],
            'total_purchase_amount' => $history['total_purchase_amount'],
            'closing_qty' => $history['closing_qty'],
            'transactions' => $history['rows'],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Create item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:50|unique:items,item_code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:goods,service',
            'category_id' => 'nullable|exists:item_categories,id',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'opening_stock' => 'nullable|numeric|min:0',
        ]);

        $validated['company_id'] = $request->user()->company_id;
        $item = $this->itemService->create($validated);

        return ResponseHelper::success(new ItemResource($item), 'Item created', 201);
    }

    /**
     * Update item.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:goods,service',
            'category_id' => 'nullable|exists:item_categories,id',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:20',
        ]);

        $this->itemService->update($id, $validated);

        return ResponseHelper::success(new ItemResource($item->fresh()), 'Item updated');
    }

    /**
     * Delete item.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        try {
            $this->itemService->delete($id);

            return ResponseHelper::success(null, 'Item deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Toggle item status.
     */
    public function status(int $id): JsonResponse
    {
        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        try {
            $item = $this->itemService->toggleStatus($id);

            return ResponseHelper::success(new ItemResource($item), 'Status updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get items for dropdown.
     */
    public function dropdown(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        return ResponseHelper::success($this->itemService->getAll($companyId));
    }

    /**
     * Get low stock items.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $items = $this->itemService->getLowStock($companyId);

        return ResponseHelper::success(ItemResource::collection($items));
    }
}
