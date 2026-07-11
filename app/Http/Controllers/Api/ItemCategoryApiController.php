<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemCategoryRequest;
use App\Services\ItemCategoryService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemCategoryApiController extends Controller
{
    protected ItemCategoryService $itemCategoryService;

    public function __construct(ItemCategoryService $itemCategoryService)
    {
        $this->itemCategoryService = $itemCategoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $filters = $request->only(['search', 'is_active']);
        $perPage = (int) $request->input('per_page', 15);

        $categories = $this->itemCategoryService->getPaginated($companyId, $filters, $perPage);

        return ResponseHelper::success([
            'data' => $categories->items(),
            'current_page' => $categories->currentPage(),
            'last_page' => $categories->lastPage(),
            'per_page' => $categories->perPage(),
            'total' => $categories->total(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->itemCategoryService->getById($id);

        if (!$category || $category->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Item category not found');
        }

        return ResponseHelper::success($category);
    }

    public function store(ItemCategoryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = $request->user()->company_id;

            $category = $this->itemCategoryService->create($validated);

            return ResponseHelper::success($category, 'Item category created successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(ItemCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->itemCategoryService->getById($id);

            if (!$category || $category->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Item category not found');
            }

            $this->itemCategoryService->update($id, $request->validated());

            return ResponseHelper::success(
                $this->itemCategoryService->getById($id),
                'Item category updated successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $category = $this->itemCategoryService->getById($id);

            if (!$category || $category->company_id !== request()->user()->company_id) {
                return ResponseHelper::notFound('Item category not found');
            }

            $this->itemCategoryService->delete($id);

            return ResponseHelper::success(null, 'Item category deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function status(int $id): JsonResponse
    {
        try {
            $category = $this->itemCategoryService->getById($id);

            if (!$category || $category->company_id !== request()->user()->company_id) {
                return ResponseHelper::notFound('Item category not found');
            }

            $category = $this->itemCategoryService->toggleStatus($id);

            return ResponseHelper::success($category, 'Status updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function dropdown(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        return ResponseHelper::success(
            $this->itemCategoryService->getAll($companyId)
        );
    }
}
