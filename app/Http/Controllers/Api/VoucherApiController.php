<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoucherResource;
use App\Http\Requests\Api\VoucherApiRequest;
use App\Http\Requests\Api\PaymentVoucherApiRequest;
use App\Http\Requests\Api\ReceiptVoucherApiRequest;
use App\Http\Requests\Api\AdjustmentVoucherApiRequest;
use App\Services\VoucherService;
use App\Helpers\ResponseHelper;
use App\Models\Voucher;
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
     * Update voucher
     */
    public function update(VoucherApiRequest $request, int $id): JsonResponse
    {
        try {
            $voucher = $this->voucherService->getById($id);

            if (!$voucher || $voucher->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Voucher not found');
            }

            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;
            $data['updated_by_ip'] = $request->ip();

            $updated = $this->voucherService->update($id, $data);

            if (!$updated) {
                return ResponseHelper::notFound('Voucher not found');
            }

            return ResponseHelper::success(
                new VoucherResource($this->voucherService->getById($id)),
                'Voucher updated successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete voucher
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $voucher = $this->voucherService->getById($id);

            if (!$voucher || $voucher->company_id !== request()->user()->company_id) {
                return ResponseHelper::notFound('Voucher not found');
            }

            $deleted = $this->voucherService->delete($id);

            if (!$deleted) {
                return ResponseHelper::notFound('Voucher not found');
            }

            return ResponseHelper::success(null, 'Voucher deleted successfully');
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

    public function indexPayments(Request $request): JsonResponse
    {
        $request->merge(['voucher_type' => 'payment']);

        return $this->index($request);
    }

    public function storePayment(PaymentVoucherApiRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    public function showPayment(int $id): JsonResponse
    {
        return $this->showTyped($id, ['payment']);
    }

    public function updatePayment(PaymentVoucherApiRequest $request, int $id): JsonResponse
    {
        return $this->updateTyped($request, $id, ['payment']);
    }

    public function destroyPayment(int $id): JsonResponse
    {
        return $this->destroyTyped($id, ['payment']);
    }

    public function cancelPayment(int $id): JsonResponse
    {
        return $this->cancelTyped($id, ['payment']);
    }

    public function indexReceipts(Request $request): JsonResponse
    {
        $request->merge(['voucher_type' => 'receipt']);

        return $this->index($request);
    }

    public function storeReceipt(ReceiptVoucherApiRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    public function showReceipt(int $id): JsonResponse
    {
        return $this->showTyped($id, ['receipt']);
    }

    public function updateReceipt(ReceiptVoucherApiRequest $request, int $id): JsonResponse
    {
        return $this->updateTyped($request, $id, ['receipt']);
    }

    public function destroyReceipt(int $id): JsonResponse
    {
        return $this->destroyTyped($id, ['receipt']);
    }

    public function cancelReceipt(int $id): JsonResponse
    {
        return $this->cancelTyped($id, ['receipt']);
    }

    public function indexAdjustments(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
        $filters['company_id'] = $request->user()->company_id;
        $filters['voucher_types'] = ['journal', 'adjustment'];

        $vouchers = $this->voucherService->getPaginated($filters, $request->input('per_page', 15));

        return ResponseHelper::success([
            'data' => VoucherResource::collection($vouchers->items()),
            'current_page' => $vouchers->currentPage(),
            'last_page' => $vouchers->lastPage(),
            'per_page' => $vouchers->perPage(),
            'total' => $vouchers->total(),
        ]);
    }

    public function storeAdjustment(AdjustmentVoucherApiRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    public function showAdjustment(int $id): JsonResponse
    {
        return $this->showTyped($id, ['journal', 'adjustment']);
    }

    public function updateAdjustment(AdjustmentVoucherApiRequest $request, int $id): JsonResponse
    {
        return $this->updateTyped($request, $id, ['journal', 'adjustment']);
    }

    public function destroyAdjustment(int $id): JsonResponse
    {
        return $this->destroyTyped($id, ['journal', 'adjustment']);
    }

    public function cancelAdjustment(int $id): JsonResponse
    {
        return $this->cancelTyped($id, ['journal', 'adjustment']);
    }

    private function showTyped(int $id, array $types): JsonResponse
    {
        $voucher = $this->findTypedVoucher($id, $types);

        if (!$voucher) {
            return ResponseHelper::notFound('Voucher not found');
        }

        return ResponseHelper::success(new VoucherResource($voucher));
    }

    private function updateTyped(VoucherApiRequest $request, int $id, array $types): JsonResponse
    {
        if (!$this->findTypedVoucher($id, $types)) {
            return ResponseHelper::notFound('Voucher not found');
        }

        return $this->update($request, $id);
    }

    private function destroyTyped(int $id, array $types): JsonResponse
    {
        if (!$this->findTypedVoucher($id, $types)) {
            return ResponseHelper::notFound('Voucher not found');
        }

        return $this->destroy($id);
    }

    private function cancelTyped(int $id, array $types): JsonResponse
    {
        if (!$this->findTypedVoucher($id, $types)) {
            return ResponseHelper::notFound('Voucher not found');
        }

        return $this->cancel($id);
    }

    private function findTypedVoucher(int $id, array $types): ?Voucher
    {
        $voucher = $this->voucherService->getById($id);

        if (!$voucher || $voucher->company_id !== request()->user()->company_id) {
            return null;
        }

        if (!in_array($voucher->voucher_type, $types, true)) {
            return null;
        }

        return $voucher;
    }
}
