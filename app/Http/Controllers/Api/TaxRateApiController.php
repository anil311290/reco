<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tax_name' => 'required|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_type' => 'required|in:addition,deduction',
            'tax_category' => 'required|in:GST,CGST,SGST,IGST,TDS,TCS,CESS,OTHER',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $validated['company_id'] = $request->user()->company_id;
        $validated['status'] = $validated['status'] ?? 'active';
        $taxRate = $this->taxRateService->create($validated);

        return ResponseHelper::success(new TaxRateResource($taxRate), 'Tax rate created', 201);
    }

    /**
     * Update tax rate.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $taxRate = $this->taxRateService->getById($id);

        if (!$taxRate || $taxRate->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Tax rate not found');
        }

        $validated = $request->validate([
            'tax_name' => 'sometimes|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'tax_type' => 'sometimes|in:addition,deduction',
            'tax_category' => 'sometimes|in:GST,CGST,SGST,IGST,TDS,TCS,CESS,OTHER',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $this->taxRateService->update($id, $validated);

        return ResponseHelper::success(new TaxRateResource($taxRate->fresh()), 'Tax rate updated');
    }
}
