<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SalesInvoiceService;
use App\Services\PartyService;
use App\Services\AccountService;
use App\Services\TaxRateService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceSalesInvoiceController extends Controller
{
    protected SalesInvoiceService $salesInvoiceService;
    protected PartyService $partyService;
    protected AccountService $accountService;
    protected TaxRateService $taxRateService;

    public function __construct(
        SalesInvoiceService $salesInvoiceService,
        PartyService $partyService,
        AccountService $accountService,
        TaxRateService $taxRateService
    ) {
        $this->salesInvoiceService = $salesInvoiceService;
        $this->partyService = $partyService;
        $this->accountService = $accountService;
        $this->taxRateService = $taxRateService;
    }

    /**
     * Display service sales invoices list.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = auth()->user()->company_id;
            $filters = ['invoice_type' => 'service'];

            if ($request->filled('status')) {
                $filters['status'] = $request->input('status');
            }
            if ($request->filled('party_id')) {
                $filters['party_id'] = $request->input('party_id');
            }

            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) {
                $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            }

            $perPage = $request->input('length', 15);
            $invoices = $this->salesInvoiceService->getPaginated($companyId, $filters, (int) $perPage);

            return response()->json([
                'data' => $invoices->items(),
                'recordsTotal' => $invoices->total(),
                'recordsFiltered' => $invoices->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.service-sales-invoices.index');
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $companyId = auth()->user()->company_id;
        $fyId = auth()->user()->company->currentFinancialYear?->id;

        $parties = $this->partyService->getAll(['company_id' => $companyId, 'type' => 'debtor']);
        $taxRates = $this->taxRateService->getAll($companyId);
        $serviceAccounts = $this->accountService->getForDropdown($companyId, 'income');
        $invoiceNumber = $fyId ? $this->salesInvoiceService->generateInvoiceNumber($companyId, $fyId, 'service') : 'SRV-000001';

        return view('admin.service-sales-invoices.create', compact('parties', 'taxRates', 'serviceAccounts', 'invoiceNumber'));
    }

    /**
     * Store new service sales invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        
        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id,company_id,' . $companyId,
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string|max:100',
            'service_lines' => 'required|array|min:1',
            'service_lines.*.account_id' => 'nullable|exists:accounts,id',
            'service_lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $fyId = auth()->user()->company->currentFinancialYear?->id;
            $invoiceNum = $fyId ? $this->salesInvoiceService->generateInvoiceNumber($companyId, $fyId, 'service') : 'SRV-000001';

            $data = [
                'uuid'               => \Illuminate\Support\Str::uuid(),
                'company_id'         => $companyId,
                'financial_year_id'  => $fyId,
                'party_id'           => $validated['party_id'],
                'invoice_number'     => $invoiceNum,
                'invoice_date'       => $validated['invoice_date'],
                'due_date'           => $validated['due_date'],
                'reference_number'   => $validated['reference_number'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'payment_terms'      => $validated['payment_terms'] ?? null,
                'delivery_terms'     => $validated['delivery_terms'] ?? null,
                'created_by'         => auth()->id(),
                'invoice_type'       => 'service',
            ];

            $invoice = $this->salesInvoiceService->create($data, [], $validated['service_lines']);

            $voucher = $this->salesInvoiceService->generateVoucher($invoice);
            if (!$voucher) {
                throw new \RuntimeException('Service sales invoice created but accounting posting failed. Please check account mappings.');
            }

            return ResponseHelper::success($invoice, 'Service sales invoice created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show invoice details.
     */
    public function show(int $id)
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->invoice_type !== 'service') {
            abort(404, 'Invoice not found');
        }

        $companyId = auth()->user()->company_id;
        $financialYearId = auth()->user()->company->currentFinancialYear?->id;
        $cashBankAccounts = $this->accountService->getCashBankAccountsForMode($companyId, null, $financialYearId);

        return view('admin.service-sales-invoices.show', compact('invoice', 'cashBankAccounts'));
    }

    /**
     * Record payment against service sales invoice.
     */
    public function payment(Request $request, int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);
        if (!$invoice || $invoice->company_id !== auth()->user()->company_id || $invoice->invoice_type !== 'service') {
            return ResponseHelper::notFound('Invoice not found');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:cash,bank,od',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'payment_date' => 'nullable|date',
        ]);

        try {
            $invoice = $this->salesInvoiceService->recordPayment($id, [
                'amount' => $validated['amount'],
                'payment_mode' => $validated['payment_mode'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                'created_by' => auth()->id(),
                'created_by_ip' => $request->ip(),
            ]);

            return ResponseHelper::success($invoice, 'Payment recorded and receipt voucher posted');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $companyId = auth()->user()->company_id;
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $companyId) {
            abort(404, 'Invoice not found');
        }

        $parties = $this->partyService->getAll(['company_id' => $companyId, 'type' => 'debtor']);
        $taxRates = $this->taxRateService->getAll($companyId);
        $serviceAccounts = $this->accountService->getForDropdown($companyId, 'income');

        return view('admin.service-sales-invoices.edit', compact('invoice', 'parties', 'taxRates', 'serviceAccounts'));
    }

    /**
     * Update invoice.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $companyId) {
            return ResponseHelper::error('Invoice not found', 404);
        }

        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id,company_id,' . $companyId,
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string|max:100',
            'service_lines' => 'required|array|min:1',
            'service_lines.*.account_id' => 'nullable|exists:accounts,id',
            'service_lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $data = [
                'party_id'         => $validated['party_id'],
                'invoice_date'     => $validated['invoice_date'],
                'due_date'         => $validated['due_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'payment_terms'    => $validated['payment_terms'] ?? null,
                'delivery_terms'   => $validated['delivery_terms'] ?? null,
                'updated_by'       => auth()->id(),
                'updated_by_ip'    => $request->ip(),
            ];
            $invoice = $this->salesInvoiceService->updateWithLines($id, $data, [], $validated['service_lines']);
            return ResponseHelper::success($invoice, 'Service sales invoice updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete invoice.
     */
    public function destroy(int $id): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $companyId) {
            return ResponseHelper::error('Invoice not found', 404);
        }

        try {
            $this->salesInvoiceService->delete($id);
            return ResponseHelper::success(null, 'Service sales invoice deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
