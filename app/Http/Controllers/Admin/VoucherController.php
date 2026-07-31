<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Services\VoucherService;
use App\Services\AccountService;
use App\Services\PartyService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    protected VoucherService $voucherService;
    protected AccountService $accountService;
    protected PartyService $partyService;

    public function __construct(
        VoucherService $voucherService,
        AccountService $accountService,
        PartyService $partyService
    ) {
        $this->voucherService = $voucherService;
        $this->accountService = $accountService;
        $this->partyService = $partyService;
    }

    /**
     * Display vouchers list
     */
    public function index(Request $request, string $type = null)
    {
        if ($request->ajax()) {
            $filters = [];
            $filters['company_id'] = Auth::user()->company_id;
            if ($request->filled('voucher_type')) $filters['voucher_type'] = $request->input('voucher_type');
            if ($request->filled('status')) $filters['status'] = $request->input('status');
            if ($request->filled('date_from')) $filters['date_from'] = $request->input('date_from');
            if ($request->filled('date_to')) $filters['date_to'] = $request->input('date_to');
            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            
            if ($type) {
                $filters['voucher_type'] = $type;
            }

            $vouchers = $this->voucherService->getPaginated($filters);

            return response()->json([
                'data' => $vouchers->items(),
                'recordsTotal' => $vouchers->total(),
                'recordsFiltered' => $vouchers->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        $companyId = Auth::user()->company_id;
        $accounts = in_array($type, ['journal', 'adjustment'], true)
            ? $this->accountService->getAdjustmentParticularsOptions($companyId)
            : $this->accountService->getForDropdown($companyId);
        $parties = $this->partyService->getForDropdown($companyId);

        return view('admin.vouchers.index', compact('type', 'accounts', 'parties'));
    }

    /**
     * Show create form
     */
    public function create(string $type)
    {
        // Sales / Purchase are invoice modules — do not open generic voucher forms
        if ($type === 'income') {
            return redirect()->route('admin.sales-invoices.create');
        }
        if ($type === 'expense') {
            return redirect()->route('admin.purchase-invoices.create');
        }

        if (!in_array($type, ['payment', 'receipt', 'journal', 'adjustment'], true)) {
            abort(404);
        }

        $companyId = Auth::user()->company_id;
        $financialYearId = Auth::user()->company->currentFinancialYear?->id;
        $accounts = in_array($type, ['journal', 'adjustment'], true)
            ? $this->accountService->getAdjustmentParticularsOptions($companyId)
            : $this->accountService->getForDropdown($companyId);
        $parties = $this->partyService->getForDropdown($companyId);
        $cashBankAccounts = in_array($type, ['payment', 'receipt'], true)
            ? $this->accountService->getCashBankAccountsForMode($companyId, $financialYearId)
            : [];
        $particularsOptions = in_array($type, ['payment', 'receipt'], true)
            ? $this->accountService->getPaymentParticularsOptions($companyId, $type)
            : [];

        return view('admin.vouchers.create', compact(
            'type',
            'accounts',
            'parties',
            'cashBankAccounts',
            'particularsOptions'
        ));
    }

    /**
     * Store new voucher
     */
    public function store(VoucherRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = Auth::user()->company_id;
            $data['financial_year_id'] = Auth::user()->company->currentFinancialYear?->id;
            $data['created_by'] = Auth::id();
            $data['created_by_ip'] = request()->ip();

            $voucher = $this->voucherService->create($data);

            return ResponseHelper::success($voucher, 'Voucher created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show voucher details
     */
    public function show(int $id)
    {
        $voucher = $this->voucherService->getById($id);

        if (!$voucher || $voucher->company_id !== Auth::user()->company_id) {
            return ResponseHelper::notFound('Voucher not found');
        }

        return view('admin.vouchers.show', compact('voucher'));
    }

    /**
     * Show edit form
     */
    public function edit(int $id)
    {
        $voucher = $this->voucherService->getById($id);

        if (!$voucher || $voucher->company_id !== Auth::user()->company_id) {
            return ResponseHelper::notFound('Voucher not found');
        }

        $companyId = Auth::user()->company_id;
        $financialYearId = Auth::user()->company->currentFinancialYear?->id;
        $accounts = in_array($voucher->voucher_type, ['journal', 'adjustment'], true)
            ? $this->accountService->getAdjustmentParticularsOptions($companyId)
            : $this->accountService->getForDropdown($companyId);
        $parties = $this->partyService->getForDropdown($companyId);
        $cashBankAccounts = in_array($voucher->voucher_type, ['payment', 'receipt'], true)
            ? $this->accountService->getCashBankAccountsForMode($companyId, $financialYearId)
            : [];
        $particularsOptions = in_array($voucher->voucher_type, ['payment', 'receipt'], true)
            ? $this->accountService->getPaymentParticularsOptions($companyId, $voucher->voucher_type)
            : [];

        return view('admin.vouchers.edit', compact(
            'voucher',
            'accounts',
            'parties',
            'cashBankAccounts',
            'particularsOptions'
        ));
    }

    /**
     * Update voucher
     */
    public function update(VoucherRequest $request, int $id): JsonResponse
    {
        try {
            $voucher = $this->voucherService->getById($id);
            if (!$voucher || $voucher->company_id !== Auth::user()->company_id) {
                return ResponseHelper::notFound('Voucher not found');
            }

            $data = $request->validated();
            $data['updated_by'] = Auth::id();
            $data['updated_by_ip'] = request()->ip();

            $updated = $this->voucherService->update($id, $data);

            if (!$updated) {
                return ResponseHelper::notFound('Voucher not found');
            }

            return ResponseHelper::success(null, 'Voucher updated successfully');
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
            if (!$voucher || $voucher->company_id !== Auth::user()->company_id) {
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
            if (!$voucher || $voucher->company_id !== Auth::user()->company_id) {
                return ResponseHelper::notFound('Voucher not found');
            }

            $posted = $this->voucherService->post($id);

            if (!$posted) {
                return ResponseHelper::notFound('Voucher not found');
            }

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
            if (!$voucher || $voucher->company_id !== Auth::user()->company_id) {
                return ResponseHelper::notFound('Voucher not found');
            }

            $cancelled = $this->voucherService->cancel($id);

            if (!$cancelled) {
                return ResponseHelper::notFound('Voucher not found');
            }

            return ResponseHelper::success(null, 'Voucher cancelled successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
