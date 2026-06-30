<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemCategoryRequest;
use App\Services\ItemCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemCategoryController extends Controller
{
    protected ItemCategoryService $itemCategoryService;

    public function __construct(ItemCategoryService $itemCategoryService)
    {
        $this->itemCategoryService = $itemCategoryService;
    }

    /**
     * Display item categories list.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = Auth::user()->company_id;
            $filters = [];

            if ($request->filled('is_active')) {
                $filters['is_active'] = $request->input('is_active');
            }

            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) {
                $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            }

            $perPage = $request->input('length', 15);
            $itemCategories = $this->itemCategoryService->getPaginated($companyId, $filters, (int) $perPage);

            return response()->json([
                'data' => $itemCategories->items(),
                'recordsTotal' => $itemCategories->total(),
                'recordsFiltered' => $itemCategories->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.item-categories.index');
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('admin.item-categories.create');
    }

    /**
     * Store new item category.
     */
    public function store(ItemCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $validated['company_id'] = Auth::user()->company_id;
            $itemCategory = $this->itemCategoryService->create($validated);
            return ResponseHelper::success($itemCategory, 'Item category created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $itemCategory = $this->itemCategoryService->getById($id);
        return view('admin.item-categories.edit', compact('itemCategory'));
    }

    /**
     * Update item category.
     */
    public function update(ItemCategoryRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->itemCategoryService->update($id, $validated);
            return ResponseHelper::success(null, 'Item category updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete item category.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->itemCategoryService->delete($id);
            return ResponseHelper::success(null, 'Item category deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Toggle status.
     */
    public function status(int $id): JsonResponse
    {
        try {
            $itemCategory = $this->itemCategoryService->toggleStatus($id);
            return ResponseHelper::success($itemCategory, 'Status updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get item categories for dropdown.
     */
    public function dropdown(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $itemCategories = $this->itemCategoryService->getAll($companyId);
        return ResponseHelper::success($itemCategories);
    }
}
