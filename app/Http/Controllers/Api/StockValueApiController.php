<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockValueRequest;
use App\Helpers\ResponseHelper;
use App\Models\StockValueEntry;
use App\Services\StockValueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockValueApiController extends Controller
{
    public function __construct(protected StockValueService $stockValueService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'financial_year_id' => 'required|integer|exists:financial_years,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $companyId = $request->user()->company_id;
        $entries = $this->stockValueService->list(
            $companyId,
            (int) $request->financial_year_id,
            $request->input('from_date'),
            $request->input('to_date'),
        );

        return ResponseHelper::success(
            $entries->map(fn (StockValueEntry $entry) => $this->serializeEntry($entry))->values()
        );
    }

    public function store(StockValueRequest $request): JsonResponse
    {
        try {
            $entry = $this->stockValueService->save(
                $request->user()->company_id,
                (int) $request->validated('financial_year_id'),
                $request->validated()
            );
        } catch (\Throwable $exception) {
            return ResponseHelper::error($exception->getMessage(), 422);
        }

        return ResponseHelper::success(
            $this->serializeEntry($entry),
            'Stock value saved successfully.',
            201
        );
    }

    public function update(StockValueRequest $request, int $entry): JsonResponse
    {
        try {
            $model = StockValueEntry::query()
                ->where('company_id', $request->user()->company_id)
                ->findOrFail($entry);

            $saved = $this->stockValueService->save(
                $request->user()->company_id,
                (int) $model->financial_year_id,
                $request->validated(),
                $model->id
            );
        } catch (\Throwable $exception) {
            return ResponseHelper::error($exception->getMessage(), 422);
        }

        return ResponseHelper::success(
            $this->serializeEntry($saved),
            'Stock value updated successfully.'
        );
    }

    private function serializeEntry(StockValueEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'financial_year_id' => $entry->financial_year_id,
            'valuation_date' => $entry->valuation_date?->toDateString(),
            'stock_value' => (float) $entry->stock_value,
            'remarks' => $entry->remarks,
            'updated_at' => $entry->updated_at?->toIso8601String(),
        ];
    }
}
