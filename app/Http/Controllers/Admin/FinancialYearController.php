<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Helpers\ResponseHelper;
use App\Services\FinancialYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialYearController extends Controller
{
    public function __construct(protected FinancialYearService $financialYearService)
    {
    }
    /**
     * Display financial years list
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = auth()->user()->company_id;
            $financialYears = FinancialYear::where('company_id', $companyId)
                ->orderBy('start_date', 'desc')
                ->get();

            return response()->json([
                'data' => $financialYears,
            ]);
        }

        return view('admin.financial-years.index');
    }

    /**
     * Store new financial year
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $companyId = auth()->user()->company_id;

            // Check for overlapping financial years
            $overlapping = FinancialYear::where('company_id', $companyId)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                        ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                        ->orWhere(function ($q) use ($request) {
                            $q->where('start_date', '<=', $request->start_date)
                              ->where('end_date', '>=', $request->end_date);
                        });
                })
                ->exists();

            if ($overlapping) {
                return ResponseHelper::error('Financial year overlaps with existing year');
            }

            $financialYear = FinancialYear::create([
                'company_id' => $companyId,
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_current' => false,
            ]);

            return ResponseHelper::success($financialYear, 'Financial year created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Set financial year as current
     */
    public function setAsCurrent(int $id): JsonResponse
    {
        try {
            $financialYear = FinancialYear::where('company_id', auth()->user()->company_id)
                ->findOrFail($id);

            if ($financialYear->is_closed) {
                return ResponseHelper::error('Cannot set a closed financial year as current');
            }

            $this->financialYearService->setAsCurrent($financialYear);

            return ResponseHelper::success(null, 'Financial year set as current successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Close financial year
     */
    public function close(int $id): JsonResponse
    {
        try {
            $financialYear = FinancialYear::where('company_id', auth()->user()->company_id)
                ->findOrFail($id);

            if ($financialYear->is_closed) {
                return ResponseHelper::error('Financial year is already closed');
            }

            $financialYear->close();

            return ResponseHelper::success(null, 'Financial year closed successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete financial year
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $financialYear = FinancialYear::where('company_id', auth()->user()->company_id)
                ->findOrFail($id);

            if ($financialYear->is_current) {
                return ResponseHelper::error('Cannot delete the current financial year');
            }

            $financialYear->delete();

            return ResponseHelper::success(null, 'Financial year deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
