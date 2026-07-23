<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxRateRequest;
use App\Http\Resources\TaxRateResource;
use App\Services\TaxRateService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxRateApiController extends Controller
{
    protected TaxRateService $taxRateService;

    public function __construct(TaxRateService $taxRateService)
    {
        $this->taxRateService = $taxRateService;
    }

    /**
     * Get all tax rates.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $taxRates = $this->taxRateService->getAll($companyId);

        return ResponseHelper::success(TaxRateResource::collection($taxRates));
    }

    /**
     * Get tax rate by ID.
     */
    public function show(int $id): JsonResponse
    {
        $taxRate = $this->taxRateService->getById($id);

        if (!$taxRate || $taxRate->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Tax rate not found');
        }

        return ResponseHelper::success(new TaxRateResource($taxRate));
    }

    /**
     * Create tax rate.
     */
    public function store(TaxRateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['company_id'] = $request->user()->company_id;
        $validated['status'] = $validated['status'] ?? 'active';
        $taxRate = $this->taxRateService->create($validated);

        return ResponseHelper::success(new TaxRateResource($taxRate), 'Tax rate created', 201);
    }

    /**
     * Update tax rate.
     */
    public function update(TaxRateRequest $request, int $id): JsonResponse
    {
        $taxRate = $this->taxRateService->getById($id);

        if (!$taxRate || $taxRate->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Tax rate not found');
        }

        $this->taxRateService->update($id, $request->validated());

        return ResponseHelper::success(new TaxRateResource($taxRate->fresh()), 'Tax rate updated');
    }

    /**
     * Delete tax rate.
     */
    public function destroy(int $id): JsonResponse
    {
        $taxRate = $this->taxRateService->getById($id);

        if (!$taxRate || $taxRate->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Tax rate not found');
        }

        try {
            $this->taxRateService->delete($id);

            return ResponseHelper::success(null, 'Tax rate deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Toggle tax rate status.
     */
    public function status(int $id): JsonResponse
    {
        $taxRate = $this->taxRateService->getById($id);

        if (!$taxRate || $taxRate->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Tax rate not found');
        }

        try {
            $taxRate = $this->taxRateService->toggleStatus($id);

            return ResponseHelper::success(new TaxRateResource($taxRate), 'Status updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get tax rates for dropdown.
     */
    public function dropdown(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        return ResponseHelper::success($this->taxRateService->getAll($companyId));
    }
}
