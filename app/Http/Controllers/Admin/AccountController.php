<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountRequest;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Services\AccountService;
use App\Services\LedgerService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    protected AccountService $accountService;
    protected LedgerService $ledgerService;

    public function __construct(AccountService $accountService, LedgerService $ledgerService)
    {
        $this->accountService = $accountService;
        $this->ledgerService = $ledgerService;
    }

    /**
     * Display accounts list
     */
    public function index(Request $request)
    {
        $companyId = request()->user()->company_id;
        $this->accountService->ensureDefaultLedgersAndCleanupDuplicates($companyId);

        if ($request->ajax()) {
            $filters = [];
            $filters['company_id'] = $companyId;
            
            if ($request->filled('account_type')) {
                $filters['account_type'] = $request->input('account_type');
            }
            if ($request->filled('is_active')) {
                $filters['is_active'] = (int) $request->input('is_active');
            }
            // DataTables sends search as an object {value: "...", regex: false}
            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) {
                $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            }
            
            $perPage = $request->input('length', 15);
            $accounts = $this->accountService->getPaginated($filters, (int) $perPage);
            $financialYearId = FinancialYear::getCurrent($filters['company_id'])?->id;

            $accounts->getCollection()->transform(function (Account $account) use ($filters, $financialYearId) {
                $balance = $this->ledgerService->getAccountBalance($account->id, $filters['company_id'], $financialYearId);
                $account->opening_balance = $balance['balance'];
                return $account;
            });

            return response()->json([
                'data' => $accounts->items(),
                'recordsTotal' => $accounts->total(),
                'recordsFiltered' => $accounts->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.accounts.index');
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        $companyId = request()->user()->company_id;
        $this->accountService->ensureDefaultLedgersAndCleanupDuplicates($companyId);

        $parentAccounts = $this->accountService->getForDropdown($companyId);
        
        // Read query parameters for pre-selection
        $accountType = $request->query('type'); // e.g., 'income' from Item/Service redirect
        $purpose = $request->query('purpose'); // e.g., 'service-item'

        return view('admin.accounts.create', compact('parentAccounts', 'accountType', 'purpose'));
    }

    /**
     * Store new account
     */
    public function store(AccountRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = request()->user()->company_id;

            $account = $this->accountService->create($data);

            return ResponseHelper::success($account, 'Account created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(int $id)
    {
        $companyId = request()->user()->company_id;
        $this->accountService->ensureDefaultLedgersAndCleanupDuplicates($companyId);

        $account = $this->accountService->getById($id);
        $parentAccounts = $this->accountService->getForDropdown($companyId);

        if (!$account || $account->company_id !== $companyId) {
            return ResponseHelper::notFound('Account not found');
        }

        $isInUse = $this->accountService->isAccountInUse($account->id);

        return view('admin.accounts.edit', compact('account', 'parentAccounts', 'isInUse'));
    }

    /**
     * Update account
     */
    public function update(AccountRequest $request, int $id): JsonResponse
    {
        try {
            $account = $this->accountService->getById($id);
            if (!$account || $account->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Account not found');
            }

            $data = $request->validated();

            $updated = $this->accountService->update($id, $data);

            if (!$updated) {
                return ResponseHelper::notFound('Account not found');
            }

            return ResponseHelper::success(null, 'Account updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete account
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $account = $this->accountService->getById($id);
            if (!$account || $account->company_id !== request()->user()->company_id) {
                return ResponseHelper::notFound('Account not found');
            }

            $deleted = $this->accountService->delete($id);

            if (!$deleted) {
                return ResponseHelper::notFound('Account not found');
            }

            return ResponseHelper::success(null, 'Account deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Change account status
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);
        $status = (bool) $validated['status'];

        try {
            $account = $this->accountService->getById($id);
            if (!$account || $account->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Account not found');
            }

            $updated = $this->accountService->update($id, [
                'is_active' => $status,
            ]);

            if (!$updated) {
                return ResponseHelper::notFound('Account not found');
            }

            $statusText = $status ? 'activated' : 'deactivated';
            return ResponseHelper::success(null, "Account {$statusText} successfully");
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get accounts by type (for AJAX)
     */
    public function getByType(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:asset,liability,income,expense,equity',
        ]);

        $companyId = request()->user()->company_id;
        $accounts = $this->accountService->getForDropdown($companyId, $request->type);
        $nextAccountCode = Account::generateCode($request->type, $companyId);

        return response()->json([
            'accounts' => $accounts,
            'next_account_code' => $nextAccountCode,
        ]);
    }

    /**
     * Get account tree
     */
    public function tree(Request $request): JsonResponse
    {
        $companyId = request()->user()->company_id;
        $type = $request->input('type');
        
        $tree = $this->accountService->getTree($companyId, $type);

        return response()->json($tree);
    }

    /**
     * Export accounts to Excel
     */
    public function exportExcel(Request $request)
    {
        $filters = ['company_id' => request()->user()->company_id];
        
        if ($request->filled('account_type')) {
            $filters['account_type'] = $request->input('account_type');
        }
        if ($request->filled('is_active')) {
            $filters['is_active'] = (int) $request->input('is_active');
        }

        $accounts = $this->accountService->getAll($filters);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AccountsExport($accounts),
            'accounts_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export accounts to PDF
     */
    public function exportPdf(Request $request)
    {
        $filters = ['company_id' => request()->user()->company_id];
        
        if ($request->filled('account_type')) {
            $filters['account_type'] = $request->input('account_type');
        }
        if ($request->filled('is_active')) {
            $filters['is_active'] = (int) $request->input('is_active');
        }

        $accounts = $this->accountService->getAll($filters);

        $pdf = \PDF::loadView('admin.accounts.export-pdf', ['accounts' => $accounts]);
        return $pdf->download('accounts_' . date('Y-m-d_H-i-s') . '.pdf');
    }
}
