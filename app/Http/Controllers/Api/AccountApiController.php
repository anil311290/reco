<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
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

    /**
     * Get all accounts
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'account_type', 'is_active']);
        $filters['company_id'] = $request->user()->company_id;

        $accounts = $this->accountService->getAll($filters);

        return ResponseHelper::success(
            AccountResource::collection($accounts)
        );
    }

    /**
     * Get account by ID
     */
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

    /**
     * Get accounts by type
     */
    public function getByType(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:asset,liability,income,expense,equity',
        ]);

        $companyId = $request->user()->company_id;
        $accounts = $this->accountService->getForDropdown($companyId, $request->type);

        return ResponseHelper::success($accounts);
    }
}
