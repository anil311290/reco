<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\AccountService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountApiController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $this->accountService->ensureDefaultLedgersAndCleanupDuplicates($companyId);

        $filters = $request->only(['search', 'account_type', 'is_active']);
        $filters['company_id'] = $companyId;

        $accounts = $this->accountService->getAll($filters);

        return ResponseHelper::success(
            AccountResource::collection($accounts)
        );
    }

    public function show(int $id): JsonResponse
    {
        $account = $this->accountService->getById($id);

        if (!$account || $account->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Account not found');
        }

        return ResponseHelper::success(
            new AccountResource($account)
        );
    }

    public function store(AccountRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $request->user()->company_id;

            $duplicateAction = $data['duplicate_action'] ?? null;
            $deletedAccount = $this->accountService->findDeletedByNameAndType(
                $request->user()->company_id,
                $data['account_name'],
                $data['account_type']
            );

            if ($deletedAccount && !$duplicateAction) {
                return response()->json([
                    'success' => false,
                    'code' => 'SOFT_DELETED_ACCOUNT_EXISTS',
                    'message' => 'A deleted account with this name and type already exists.',
                    'data' => [
                        'account_code' => $deletedAccount->account_code,
                        'account_name' => $deletedAccount->account_name,
                        'account_type' => $deletedAccount->account_type,
                    ],
                ], 409);
            }

            if ($deletedAccount && $duplicateAction === 'restore') {
                $account = $this->accountService->restoreDeleted($deletedAccount, $data);

                return ResponseHelper::success(
                    new AccountResource($account),
                    'Account restored successfully'
                );
            }

            unset($data['duplicate_action']);
            $account = $this->accountService->create($data);

            return ResponseHelper::success(
                new AccountResource($account),
                'Account created successfully',
                201
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(AccountRequest $request, int $id): JsonResponse
    {
        try {
            $account = $this->accountService->getById($id);

            if (!$account || $account->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Account not found');
            }

            $updated = $this->accountService->update($id, $request->validated());

            if (!$updated) {
                return ResponseHelper::notFound('Account not found');
            }

            return ResponseHelper::success(
                new AccountResource($this->accountService->getById($id)),
                'Account updated successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

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

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        try {
            $account = $this->accountService->getById($id);

            if (!$account || $account->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Account not found');
            }

            $updated = $this->accountService->update($id, [
                'is_active' => $request->status,
            ]);

            if (!$updated) {
                return ResponseHelper::notFound('Account not found');
            }

            $statusText = $request->status ? 'activated' : 'deactivated';

            return ResponseHelper::success(null, "Account {$statusText} successfully");
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function getByType(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:asset,liability,income,expense,equity',
        ]);

        $companyId = $request->user()->company_id;
        $accounts = $this->accountService->getForDropdown($companyId, $request->type);
        $nextAccountCode = Account::generateCode($request->type, $companyId);

        return ResponseHelper::success([
            'accounts' => $accounts,
            'next_account_code' => $nextAccountCode,
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $type = $request->input('type');

        return ResponseHelper::success(
            $this->accountService->getTree($companyId, $type)
        );
    }

    /**
     * Cash / Bank / OD accounts for payment, receipt, and invoice settlement forms.
     */
    public function cashBank(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => 'nullable|in:cash,bank,od',
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = isset($validated['financial_year_id'])
            ? (int) $validated['financial_year_id']
            : $request->user()->company->currentFinancialYear?->id;

        return ResponseHelper::success(
            $this->accountService->getCashBankAccountsForMode(
                $companyId,
                $validated['mode'] ?? null,
                $financialYearId
            )
        );
    }

    /**
     * Particulars options for payment/receipt lines (parties mapped to accounts).
     */
    public function paymentParticulars(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:payment,receipt',
        ]);

        return ResponseHelper::success(
            $this->accountService->getPaymentParticularsOptions(
                $request->user()->company_id,
                $validated['type']
            )
        );
    }

    /**
     * Particulars options for adjustment/journal vouchers.
     */
    public function adjustmentParticulars(Request $request): JsonResponse
    {
        return ResponseHelper::success(
            $this->accountService->getAdjustmentParticularsOptions($request->user()->company_id)
        );
    }
}
