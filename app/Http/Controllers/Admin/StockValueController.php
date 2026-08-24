<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockValueRequest;
use App\Models\FinancialYear;
use App\Models\StockValueEntry;
use App\Services\StockValueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockValueController extends Controller
{
    public function __construct(protected StockValueService $stockValueService)
    {
    }

    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $financialYears = FinancialYear::query()
            ->where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get();
        $financialYearId = (int) ($request->input('financial_year_id') ?: $financialYears->firstWhere('is_current', true)?->id ?: $financialYears->first()?->id);
        $entries = $financialYearId ? $this->stockValueService->list($companyId, $financialYearId, $request->input('from_date'), $request->input('to_date')) : collect();

        return view('admin.reports.stock-value-register', compact('entries', 'financialYears', 'financialYearId'));
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
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $entry, 'message' => 'Stock value saved successfully.']);
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
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $saved, 'message' => 'Stock value updated successfully.']);
    }
}
