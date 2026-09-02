<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockValueRequest;
use App\Models\FinancialYear;
use App\Models\StockValueEntry;
use App\Services\StockValueService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockValueApiController extends Controller
{
    protected StockValueService $stockValueService;

    public function __construct(StockValueService $stockValueService)
    {
        $this->stockValueService = $stockValueService;
    }

    /**
     * Get stock value entries for a financial year
     */
    public function entries(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id');

        if (!$financialYearId) {
            $currentFy = FinancialYear::getCurrent($companyId);
            $financialYearId = $currentFy?->id;
        }

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found', 422);
        }

        $request->validate([
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $entries = $this->stockValueService->list(
            $companyId,
            $financialYearId,
            $request->input('from_date'),
            $request->input('to_date')
        );

        return ResponseHelper::success($entries);
    }

    /**
     * Get a single stock value entry
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $entry = StockValueEntry::query()
            ->where('company_id', $companyId)
            ->find($id);

        if (!$entry) {
            return ResponseHelper::notFound('Stock value entry not found');
        }

        return ResponseHelper::success($entry);
    }

    /**
     * Create a new stock value entry
     */
    public function store(StockValueRequest $request): JsonResponse
    {
        try {
            $entry = $this->stockValueService->save(
                $request->user()->company_id,
                (int) $request->validated('financial_year_id'),
                $request->validated()
            );

            return ResponseHelper::success($entry, 'Stock value entry created successfully', 201);
        } catch (\InvalidArgumentException $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    /**
     * Update an existing stock value entry
     */
    public function update(StockValueRequest $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $entry = StockValueEntry::query()
                ->where('company_id', $companyId)
                ->findOrFail($id);

            $updated = $this->stockValueService->save(
                $companyId,
                (int) $entry->financial_year_id,
                $request->validated(),
                $entry->id
            );

            return ResponseHelper::success($updated, 'Stock value entry updated successfully');
        } catch (\InvalidArgumentException $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    /**
     * Delete a stock value entry
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        try {
            $entry = StockValueEntry::query()
                ->where('company_id', $companyId)
                ->findOrFail($id);

            $entry->delete();

            return ResponseHelper::success(null, 'Stock value entry deleted successfully');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
