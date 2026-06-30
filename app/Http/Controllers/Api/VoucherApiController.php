<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoucherResource;
use App\Http\Requests\Api\VoucherApiRequest;
use App\Services\VoucherService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherApiController extends Controller
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Get all vouchers
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'voucher_type', 'status', 'date_from', 'date_to']);
        $filters['company_id'] = $request->user()->company_id;

        $vouchers = $this->voucherService->getPaginated($filters, $request->input('per_page', 15));

        return ResponseHelper::success([
            'data' => VoucherResource::collection($vouchers->items()),
            'current_page' => $vouchers->currentPage(),
            'last_page' => $vouchers->lastPage(),
            'per_page' => $vouchers->perPage(),
            'total' => $vouchers->total(),
        ]);
    }

    /**
     * Get voucher by ID
     */
    public function show(int $id): JsonResponse
    {
        $voucher = $this->voucherService->getById($id);

        if (!$voucher || $voucher->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Voucher not found');
        }

        return ResponseHelper::success(
            new VoucherResource($voucher)
        );
    }

    /**
     * Create voucher
     */
    public function store(VoucherApiRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $request->user()->company_id;
            $data['financial_year_id'] = $request->user()->company->currentFinancialYear?->id;
            $data['created_by'] = $request->user()->id;
            $data['created_by_ip'] = $request->ip();

            $voucher = $this->voucherService->create($data);

            return ResponseHelper::success(
                new VoucherResource($voucher),
                'Voucher created successfully',
                201
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Post voucher
     */
    public function post(int $id): JsonResponse
    {
        try {
            $voucher = $this->voucherService->getById($id);

            if (!$voucher || $voucher->company_id !== request()->user()->company_id) {
                return ResponseHelper::notFound('Voucher not found');
            }

            $posted = $this->voucherService->post($id);

            return ResponseHelper::success(null, 'Voucher posted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Cancel voucher
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $voucher = $this->voucherService->getById($id);

            if (!$voucher || $voucher->company_id !== request()->user()->company_id) {
                return ResponseHelper::notFound('Voucher not found');
            }

            $cancelled = $this->voucherService->cancel($id);

            return ResponseHelper::success(null, 'Voucher cancelled successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get voucher statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id');

        $stats = $this->voucherService->getStatistics($companyId, $financialYearId);

        return ResponseHelper::success($stats);
    }
}
