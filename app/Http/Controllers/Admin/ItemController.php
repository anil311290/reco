<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemRequest;
use App\Services\AccountService;
use App\Services\ItemCategoryService;
use App\Services\ItemService;
use App\Services\TaxRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    protected ItemService $itemService;
    protected ItemCategoryService $itemCategoryService;
    protected TaxRateService $taxRateService;
    protected AccountService $accountService;

    public function __construct(
        ItemService $itemService,
        ItemCategoryService $itemCategoryService,
        TaxRateService $taxRateService,
        AccountService $accountService
    ) {
        $this->itemService = $itemService;
        $this->itemCategoryService = $itemCategoryService;
        $this->taxRateService = $taxRateService;
        $this->accountService = $accountService;
    }

    /**
     * Display items list.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = Auth::user()->company_id;
            $filters = [];

            if ($request->filled('type')) {
                $filters['type'] = $request->input('type');
            }
            if ($request->filled('category_id')) {
                $filters['category_id'] = $request->input('category_id');
            }
            if ($request->filled('is_active')) {
                $filters['is_active'] = $request->input('is_active');
            }

            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) {
                $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            }

            $perPage = $request->input('length', 15);
            $items = $this->itemService->getPaginated($companyId, $filters, (int) $perPage);

            return response()->json([
                'data' => $items->items(),
                'recordsTotal' => $items->total(),
                'recordsFiltered' => $items->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        $companyId = Auth::user()->company_id;
        $categories = $this->itemCategoryService->getAll($companyId, false);

        return view('admin.items.index', compact('categories'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $companyId = Auth::user()->company_id;
        $categories = $this->itemCategoryService->getAll($companyId);
        $taxRates = $this->taxRateService->getAll($companyId);

        return view('admin.items.create', compact('categories', 'taxRates'));
    }

    /**
     * Store new item.
     */
    public function store(ItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $validated['company_id'] = Auth::user()->company_id;
            $item = $this->itemService->create($validated);
            return ResponseHelper::success($item, 'Item created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show item details with complete sales history.
     */
    public function show(Request $request, int $id)
    {
        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== Auth::user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        $perPage = (int) $request->input('per_page', 15);

        $history = $this->itemService->getItemHistory(
            $id,
            (int) Auth::user()->company_id,
            $request->input('date_from'),
            $request->input('date_to'),
            $perPage > 0 ? $perPage : 15
        );

        return view('admin.items.show', compact('item', 'history'));
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $item = $this->itemService->getById($id);
        $companyId = Auth::user()->company_id;
        $categories = $this->itemCategoryService->getAll($companyId);
        $taxRates = $this->taxRateService->getAll($companyId);

        return view('admin.items.edit', compact('item', 'categories', 'taxRates'));
    }

    /**
     * Update item.
     */
    public function update(ItemRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->itemService->update($id, $validated);
            return ResponseHelper::success(null, 'Item updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete item.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->itemService->delete($id);
            return ResponseHelper::success(null, 'Item deleted successfully');
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
            $item = $this->itemService->toggleStatus($id);
            return ResponseHelper::success($item, 'Status updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get items for dropdown.
     */
    public function dropdown(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $items = $this->itemService->getAll($companyId);
        return ResponseHelper::success($items);
    }

    /**
     * Get low stock items.
     */
    public function lowStock(): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $items = $this->itemService->getLowStock($companyId);
        return ResponseHelper::success($items);
    }

    /**
     * Export item stock history to Excel.
     */
    public function exportExcel(Request $request, int $id)
    {
        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== Auth::user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        $history = $this->itemService->getItemHistory(
            $id,
            (int) Auth::user()->company_id,
            $request->input('date_from'),
            $request->input('date_to'),
            0
        );

        $filename = 'item_history_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $item->item_code) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ItemHistoryExport($history['rows'], $item->name),
            $filename
        );
    }

    /**
     * Export item stock history to PDF.
     */
    public function exportPdf(Request $request, int $id)
    {
        $item = $this->itemService->getById($id);

        if (!$item || $item->company_id !== Auth::user()->company_id) {
            return ResponseHelper::notFound('Item not found');
        }

        $history = $this->itemService->getItemHistory(
            $id,
            (int) Auth::user()->company_id,
            $request->input('date_from'),
            $request->input('date_to'),
            0
        );

        $filename = 'item_history_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $item->item_code) . '_' . date('Y-m-d_H-i-s') . '.pdf';

        $pdf = \PDF::loadView('admin.items.export-pdf', compact('item', 'history'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
