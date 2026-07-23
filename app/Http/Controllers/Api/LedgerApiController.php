<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LedgerService;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LedgerApiController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Get ledger list (all accounts with balances)
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        $type = $request->input('type');
        $search = $request->input('search');

        $query = Account::where('company_id', $companyId)
            ->where('is_active', true);

        if ($type) {
            $query->where('account_type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhere('account_code', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('id', 'desc')->get();

        $ledgerSummary = $accounts->map(function ($account) use ($companyId, $financialYearId) {
            $balance = $this->ledgerService->getAccountBalance($account->id, $companyId, $financialYearId);

            return [
                'id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'type_label' => $account->type_label,
                'balance' => $balance['balance'],
                'balance_type' => $balance['type'],
            ];
        });

        return ResponseHelper::success($ledgerSummary);
    }

    /**
     * Get ledger details for an account
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $ledger = $this->ledgerService->getAccountLedger(
            $id,
            $companyId,
            $financialYearId,
            $dateFrom,
            $dateTo
        );

        return ResponseHelper::success($ledger);
    }

    /**
     * Get ledger entries for an account
     */
    public function entries(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = $request->input('per_page', 20);

        $query = \App\Models\Ledger::where('company_id', $companyId)
            ->where('account_id', $id)
            ->with(['voucher', 'account']);

        if ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        if ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }

        $entries = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return ResponseHelper::success([
            'data' => $entries->items(),
            'current_page' => $entries->currentPage(),
            'last_page' => $entries->lastPage(),
            'per_page' => $entries->perPage(),
            'total' => $entries->total(),
        ]);
    }
}
