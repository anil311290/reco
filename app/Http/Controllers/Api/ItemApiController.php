<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemRequest;
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
     * Get all items (paginated master list — same as web Items module).
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $filters = $request->only(['search', 'type', 'category_id', 'is_active']);
        $perPage = (int) $request->input('per_page', 15);

        $items = $this->itemService->getPaginated($companyId, $filters, $perPage);

        return ResponseHelper::success([
            'data' => ItemResource::collection($items->items()),
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'per_page' => $items->perPage(),
            'total' => $items->total(),
        ]);
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
    public function store(ItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['company_id'] = $request->user()->company_id;
        $validated['type'] = $validated['type'] ?? 'goods';

        $item = $this->itemService->create($validated);

        return ResponseHelper::success(new ItemResource($item), 'Item created', 201);
    }

    /**
     * Update item.
     */
    public function update(ItemRequest $request, int $id): JsonResponse
    {
        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        $this->itemService->update($id, $request->validated());

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
     * Get items + services for dropdown (sales invoice line picker).
     */
    public function dropdown(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $filters = $request->only(['search', 'type', 'is_active']);

        $catalog = $this->itemService->getSalesLineCatalog($companyId, $filters);

        return ResponseHelper::success([
            'items' => ItemResource::collection($catalog['items'])->resolve(),
            'services' => $catalog['services'],
        ]);
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
