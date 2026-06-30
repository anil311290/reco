<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxRateRequest;
use App\Services\TaxRateService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxRateController extends Controller
{
    protected TaxRateService $taxRateService;

    public function __construct(TaxRateService $taxRateService)
    {
        $this->taxRateService = $taxRateService;
    }

    /**
     * Display tax rates list.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = Auth::user()->company_id;
            $filters = [];

            if ($request->filled('tax_type')) {
                $filters['tax_type'] = $request->input('tax_type');
            }
            if ($request->filled('tax_category')) {
                $filters['tax_category'] = $request->input('tax_category');
            }
            if ($request->filled('status')) {
                $filters['status'] = $request->input('status');
            }

            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) {
                $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            }

            $perPage = $request->input('length', 15);
            $taxRates = $this->taxRateService->getPaginated($companyId, $filters, (int) $perPage);

            return response()->json([
                'data' => $taxRates->items(),
                'recordsTotal' => $taxRates->total(),
                'recordsFiltered' => $taxRates->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.tax-rates.index');
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('admin.tax-rates.create');
    }

    /**
     * Store new tax rate.
     */
    public function store(TaxRateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $validated['company_id'] = Auth::user()->company_id;
            $taxRate = $this->taxRateService->create($validated);
            return ResponseHelper::success($taxRate, 'Tax rate created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $taxRate = $this->taxRateService->getById($id);
        return view('admin.tax-rates.edit', compact('taxRate'));
    }

    /**
     * Update tax rate.
     */
    public function update(TaxRateRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->taxRateService->update($id, $validated);
            return ResponseHelper::success(null, 'Tax rate updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete tax rate.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->taxRateService->delete($id);
            return ResponseHelper::success(null, 'Tax rate deleted successfully');
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
            $taxRate = $this->taxRateService->toggleStatus($id);
            return ResponseHelper::success($taxRate, 'Status updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get tax rates for dropdown.
     */
    public function dropdown(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $taxRates = $this->taxRateService->getAll($companyId);
        return ResponseHelper::success($taxRates);
    }
}
