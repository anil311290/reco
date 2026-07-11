<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialYearApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->orderBy('start_date', 'desc')
            ->get();

        return ResponseHelper::success($financialYears);
    }

    public function current(Request $request): JsonResponse
    {
        $financialYear = FinancialYear::getCurrent($request->user()->company_id);

        if (!$financialYear) {
            return ResponseHelper::notFound('No active financial year found');
        }

        return ResponseHelper::success($financialYear);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $companyId = $request->user()->company_id;

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

            return ResponseHelper::success($financialYear, 'Financial year created successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function setAsCurrent(int $id): JsonResponse
    {
        try {
            $financialYear = FinancialYear::where('company_id', request()->user()->company_id)
                ->findOrFail($id);

            if ($financialYear->is_closed) {
                return ResponseHelper::error('Cannot set a closed financial year as current');
            }

            $financialYear->setAsCurrent();

            return ResponseHelper::success($financialYear->fresh(), 'Financial year set as current successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function close(int $id): JsonResponse
    {
        try {
            $financialYear = FinancialYear::where('company_id', request()->user()->company_id)
                ->findOrFail($id);

            if ($financialYear->is_closed) {
                return ResponseHelper::error('Financial year is already closed');
            }

            $financialYear->close();

            return ResponseHelper::success($financialYear->fresh(), 'Financial year closed successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $financialYear = FinancialYear::where('company_id', request()->user()->company_id)
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
