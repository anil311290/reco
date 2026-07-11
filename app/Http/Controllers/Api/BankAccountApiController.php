<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankAccountResource;
use App\Services\BankAccountService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountApiController extends Controller
{
    protected BankAccountService $bankAccountService;

    public function __construct(BankAccountService $bankAccountService)
    {
        $this->bankAccountService = $bankAccountService;
    }

    /**
     * Get all bank accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $bankAccounts = $this->bankAccountService->getAll($companyId);

        return ResponseHelper::success(BankAccountResource::collection($bankAccounts));
    }

    /**
     * Get bank account by ID.
     */
    public function show(int $id): JsonResponse
    {
        $bankAccount = $this->bankAccountService->getById($id);

        if (!$bankAccount || $bankAccount->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Bank account not found');
        }

        return ResponseHelper::success(new BankAccountResource($bankAccount));
    }

    /**
     * Create bank account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'account_holder_name' => 'nullable|string|max:255',
            'account_type' => 'required|in:savings,current,fixed_deposit,cc_od',
            'opening_balance' => 'nullable|numeric',
            'upi_id' => 'nullable|string|max:100',
            'is_default' => 'boolean',
        ]);

        $validated['company_id'] = $request->user()->company_id;
        $bankAccount = $this->bankAccountService->create($validated);

        return ResponseHelper::success(new BankAccountResource($bankAccount), 'Bank account created', 201);
    }

    /**
     * Update bank account.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $bankAccount = $this->bankAccountService->getById($id);

        if (!$bankAccount || $bankAccount->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Bank account not found');
        }

        $validated = $request->validate([
            'bank_name' => 'sometimes|string|max:255',
            'account_number' => 'sometimes|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'account_holder_name' => 'nullable|string|max:255',
            'account_type' => 'sometimes|in:savings,current,fixed_deposit,cc_od',
            'upi_id' => 'nullable|string|max:100',
            'is_default' => 'boolean',
        ]);

        $this->bankAccountService->update($id, $validated);

        return ResponseHelper::success(new BankAccountResource($bankAccount->fresh()), 'Bank account updated');
    }

    /**
     * Set default bank account.
     */
    public function setDefault(int $id): JsonResponse
    {
        $companyId = request()->user()->company_id;
        $this->bankAccountService->setDefault($id, $companyId);

        return ResponseHelper::success(null, 'Default bank account updated');
    }

    /**
     * Delete bank account.
     */
    public function destroy(int $id): JsonResponse
    {
        $bankAccount = $this->bankAccountService->getById($id);

        if (!$bankAccount || $bankAccount->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Bank account not found');
        }

        try {
            $this->bankAccountService->delete($id);

            return ResponseHelper::success(null, 'Bank account deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get bank accounts for dropdown.
     */
    public function dropdown(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        return ResponseHelper::success($this->bankAccountService->getAll($companyId));
    }
}
