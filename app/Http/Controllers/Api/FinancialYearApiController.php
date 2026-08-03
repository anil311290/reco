<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Helpers\ResponseHelper;
use App\Services\FinancialYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialYearApiController extends Controller
{
    public function __construct(protected FinancialYearService $financialYearService)
    {
    }
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

            $this->financialYearService->setAsCurrent($financialYear);

            return ResponseHelper::success($financialYear->fresh(), 'Financial year set as current successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function close(Request $request, int $id): JsonResponse
    {
        try {
            $financialYear = FinancialYear::where('company_id', request()->user()->company_id)
                ->findOrFail($id);

            $requestedStatus = $request->has('status')
                ? (bool) $request->boolean('status')
                : !$financialYear->is_closed;

            if ($requestedStatus) {
                // status=true means disabled/closed
                if ($financialYear->is_current) {
                    $replacementYear = FinancialYear::where('company_id', request()->user()->company_id)
                        ->where('id', '!=', $financialYear->id)
                        ->where('is_closed', false)
                        ->orderBy('start_date', 'desc')
                        ->first();

                    if (!$replacementYear) {
                        return ResponseHelper::error('Create or enable another financial year before disabling the current year');
                    }

                    $this->financialYearService->setAsCurrent($replacementYear);
                }

                $financialYear->update([
                    'is_closed' => true,
                    'is_current' => false,
                    'closed_at' => now(),
                ]);

                return ResponseHelper::success($financialYear->fresh(), 'Financial year disabled successfully');
            }

            $financialYear->update([
                'is_closed' => false,
                'closed_at' => null,
            ]);

            return ResponseHelper::success($financialYear->fresh(), 'Financial year enabled successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $companyId = request()->user()->company_id;

            $financialYear = FinancialYear::where('company_id', $companyId)
                ->findOrFail($id);

            if ($financialYear->is_current) {
                return ResponseHelper::error('Cannot delete the current financial year');
            }

            $usageCounts = $this->financialYearUsageCounts((int) $companyId, (int) $financialYear->id);
            $usageTotal = array_sum($usageCounts);

            if ($usageTotal > 0) {
                return ResponseHelper::error(
                    'Cannot delete this financial year because it is used in existing records. Disable it instead.',
                    400,
                    [
                        'usage' => $usageCounts,
                        'total_records' => $usageTotal,
                    ]
                );
            }

            $financialYear->delete();

            return ResponseHelper::success(null, 'Financial year deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $financialYear = FinancialYear::onlyTrashed()
                ->where('company_id', request()->user()->company_id)
                ->findOrFail($id);

            $financialYear->restore();

            return ResponseHelper::success($financialYear->fresh(), 'Financial year restored successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @return array<string,int>
     */
    protected function financialYearUsageCounts(int $companyId, int $financialYearId): array
    {
        return [
            'accounts' => DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->count(),
            'parties' => DB::table('parties')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->count(),
            'vouchers' => DB::table('vouchers')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->count(),
            'ledgers' => DB::table('ledgers')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->count(),
            'sales_invoices' => DB::table('sales_invoices')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->count(),
            'purchase_invoices' => DB::table('purchase_invoices')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->count(),
            'ledger_party_histories' => DB::table('ledger_party_histories')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->count(),
        ];
    }
}
