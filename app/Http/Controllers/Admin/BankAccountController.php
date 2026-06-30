<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BankAccountService;
use App\Services\AccountService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    protected BankAccountService $bankAccountService;
    protected AccountService $accountService;

    public function __construct(BankAccountService $bankAccountService, AccountService $accountService)
    {
        $this->bankAccountService = $bankAccountService;
        $this->accountService = $accountService;
    }

    /**
     * Display bank accounts list.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = auth()->user()->company_id;
            $bankAccounts = $this->bankAccountService->getAll($companyId, false);

            return response()->json([
                'data' => $bankAccounts,
                'recordsTotal' => $bankAccounts->count(),
                'recordsFiltered' => $bankAccounts->count(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.bank-accounts.index');
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $companyId = auth()->user()->company_id;
        $accounts = $this->accountService->getAll(['company_id' => $companyId, 'account_type' => 'asset']);

        return view('admin.bank-accounts.create', compact('accounts'));
    }

    /**
     * Store new bank account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'account_holder_name' => 'nullable|string|max:255',
            'account_type' => 'required|in:savings,current,fixed_deposit,cc_od',
            'opening_balance' => 'nullable|numeric',
            'opening_date' => 'nullable|date',
            'upi_id' => 'nullable|string|max:100',
            'is_default' => 'boolean',
            'remarks' => 'nullable|string',
        ]);

        try {
            $validated['company_id'] = auth()->user()->company_id;
            $bankAccount = $this->bankAccountService->create($validated);
            return ResponseHelper::success($bankAccount, 'Bank account created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $bankAccount = $this->bankAccountService->getById($id);
        $companyId = auth()->user()->company_id;
        $accounts = $this->accountService->getAll(['company_id' => $companyId, 'account_type' => 'asset']);

        return view('admin.bank-accounts.edit', compact('bankAccount', 'accounts'));
    }

    /**
     * Update bank account.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'account_holder_name' => 'nullable|string|max:255',
            'account_type' => 'required|in:savings,current,fixed_deposit,cc_od',
            'opening_balance' => 'nullable|numeric',
            'opening_date' => 'nullable|date',
            'upi_id' => 'nullable|string|max:100',
            'is_default' => 'boolean',
            'remarks' => 'nullable|string',
        ]);

        try {
            $this->bankAccountService->update($id, $validated);
            return ResponseHelper::success(null, 'Bank account updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete bank account.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->bankAccountService->delete($id);
            return ResponseHelper::success(null, 'Bank account deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Set as default.
     */
    public function setDefault(int $id): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;
            $this->bankAccountService->setDefault($id, $companyId);
            return ResponseHelper::success(null, 'Default bank account updated');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get bank accounts for dropdown.
     */
    public function dropdown(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $bankAccounts = $this->bankAccountService->getAll($companyId);
        return ResponseHelper::success($bankAccounts);
    }
}
