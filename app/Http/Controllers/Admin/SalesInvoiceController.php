<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemRequest;
use App\Services\SalesInvoiceService;
use App\Services\PartyService;
use App\Services\AccountService;
use App\Services\ItemCategoryService;
use App\Services\ItemService;
use App\Services\TaxRateService;
use App\Services\ExportService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SalesInvoiceController extends Controller
{
    protected SalesInvoiceService $salesInvoiceService;
    protected PartyService $partyService;
    protected AccountService $accountService;
    protected ItemCategoryService $itemCategoryService;
    protected ItemService $itemService;
    protected TaxRateService $taxRateService;
    protected ExportService $exportService;

    public function __construct(
        SalesInvoiceService $salesInvoiceService,
        PartyService $partyService,
        AccountService $accountService,
        ItemCategoryService $itemCategoryService,
        ItemService $itemService,
        TaxRateService $taxRateService,
        ExportService $exportService
    ) {
        $this->salesInvoiceService = $salesInvoiceService;
        $this->partyService = $partyService;
        $this->accountService = $accountService;
        $this->itemCategoryService = $itemCategoryService;
        $this->itemService = $itemService;
        $this->taxRateService = $taxRateService;
        $this->exportService = $exportService;
    }

    /**
     * Display sales invoices list.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = auth()->user()->company_id;
            $filters = [];

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

        return view('admin.sales-invoices.index');
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $companyId = auth()->user()->company_id;
        $fyId = auth()->user()->company->currentFinancialYear?->id;

        $partyOptions = $this->partyService->getInvoicePartyOptions($companyId, 'debtor');
        $goodsItems = $this->itemService->getAll($companyId, ['type' => 'goods']);
        $serviceItems = $this->itemService->getAll($companyId, ['type' => 'service']);
        $itemCategories = $this->itemCategoryService->getAll($companyId);
        $taxRates = $this->taxRateService->getAll($companyId);
        $invoiceNumber = $fyId ? $this->salesInvoiceService->generateInvoiceNumber($companyId, $fyId) : null;

        return view('admin.sales-invoices.create', compact(
            'partyOptions',
            'goodsItems',
            'serviceItems',
            'itemCategories',
            'taxRates',
            'invoiceNumber'
        ));
    }

    /**
     * Create an item or service directly from the sales invoice form.
     */
    public function quickAddItem(ItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        $item = $this->itemService->create($data);

        return ResponseHelper::success($item, 'Item created and selected successfully');
    }

    /**
     * Store new sales invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        
        $validated = $request->validate([
            'party_id' => 'required',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'nullable|array',
            'lines.*.item_id' => [
                'nullable',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'lines.*.account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'lines.*.tax_rate_id' => [
                'nullable',
                Rule::exists('tax_rates', 'id')->where('company_id', $companyId),
            ],
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'service_lines' => 'nullable|array',
            'service_lines.*.account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'service_lines.*.tax_rate_id' => [
                'nullable',
                Rule::exists('tax_rates', 'id')->where('company_id', $companyId),
            ],
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'required|numeric|min:0.01',
        ]);

        if (empty($validated['lines']) && empty($validated['service_lines'])) {
            return ResponseHelper::error('Please add at least one item or service line', 422);
        }

        $validated['lines'] = $validated['lines'] ?? [];
        $validated['service_lines'] = $validated['service_lines'] ?? [];

        try {
            $companyId = auth()->user()->company_id;
            $fyId = auth()->user()->company->currentFinancialYear?->id;
            $resolvedPartyId = $this->partyService->resolveInvoicePartySelection(
                $validated['party_id'],
                $companyId,
                'debtor',
                auth()->id(),
                $request->ip()
            );

            $data = [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'company_id' => $companyId,
                'financial_year_id' => $fyId,
                'party_id' => $resolvedPartyId,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'status' => 'draft',
            ];

            $invoice = $this->salesInvoiceService->create($data, $validated['lines'], $validated['service_lines'] ?? []);

            $voucher = $this->salesInvoiceService->generateVoucher($invoice);
            if (!$voucher) {
                throw new \RuntimeException('Sales invoice created but voucher/journal posting failed. Please check account mappings.');
            }

            return ResponseHelper::success($invoice, 'Sales invoice created successfully');
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
        $companyId = auth()->user()->company_id;
        $financialYearId = auth()->user()->company->currentFinancialYear?->id;
        $cashBankAccounts = $this->accountService->getCashBankAccountsForMode($companyId, $financialYearId);

        return view('admin.sales-invoices.show', compact('invoice', 'cashBankAccounts'));
    }

    /**
     * Export sales invoice to PDF.
     */
    public function exportPdf(int $id): Response
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice) {
            abort(404);
        }

        $pdf = $this->exportService->exportSalesInvoicePdf($id);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="invoice-' . $invoice->invoice_number . '.pdf"');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $invoice = $this->salesInvoiceService->getById($id);
        if (!$invoice) {
            abort(404);
        }
        if (in_array($invoice->status, ['paid', 'partial', 'cancelled'], true)) {
            return redirect()
                ->route('admin.sales-invoices.show', $id)
                ->with('error', 'Paid, partially paid, or cancelled invoices cannot be edited.');
        }

        $companyId = auth()->user()->company_id;
        $partyOptions = $this->partyService->getInvoicePartyOptions($companyId, 'debtor');
        $goodsItems = $this->itemService->getAll($companyId, ['type' => 'goods']);
        $serviceItems = $this->itemService->getAll($companyId, ['type' => 'service']);
        $itemCategories = $this->itemCategoryService->getAll($companyId);
        $taxRates = $this->taxRateService->getAll($companyId);

        return view('admin.sales-invoices.edit', compact(
            'invoice',
            'partyOptions',
            'goodsItems',
            'serviceItems',
            'itemCategories',
            'taxRates'
        ));
    }

    /**
     * Update sales invoice.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'party_id' => 'required',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'nullable|array',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'service_lines' => 'nullable|array',
            'service_lines.*.account_id' => 'required|exists:accounts,id',
            'service_lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'required|numeric|min:0.01',
        ]);

        if (empty($validated['lines']) && empty($validated['service_lines'])) {
            return ResponseHelper::error('Please add at least one item or service line', 422);
        }

        $validated['lines'] = $validated['lines'] ?? [];
        $validated['service_lines'] = $validated['service_lines'] ?? [];

        try {
            $resolvedPartyId = $this->partyService->resolveInvoicePartySelection(
                $validated['party_id'],
                auth()->user()->company_id,
                'debtor',
                auth()->id(),
                $request->ip()
            );

            $data = [
                'party_id' => $resolvedPartyId,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'updated_by' => auth()->id(),
                'updated_by_ip' => $request->ip(),
            ];

            $invoice = $this->salesInvoiceService->updateWithLines($id, $data, $validated['lines'], $validated['service_lines'] ?? []);
            return ResponseHelper::success($invoice, 'Sales invoice updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Record payment.
     */
    public function payment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'payment_date' => 'nullable|date',
        ]);

        try {
            $invoice = $this->salesInvoiceService->recordPayment($id, [
                'amount' => $validated['amount'],
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
     * Cancel sales invoice (reverses vouchers, ledger, and stock).
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $invoice = $this->salesInvoiceService->getById($id);
            if (!$invoice || $invoice->company_id !== auth()->user()->company_id) {
                return ResponseHelper::notFound('Sales invoice not found');
            }

            $invoice = $this->salesInvoiceService->cancel($id);
            return ResponseHelper::success($invoice, 'Sales invoice cancelled successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete sales invoice.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->salesInvoiceService->delete($id);
            return ResponseHelper::success(null, 'Sales invoice deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get overdue invoices.
     */
    public function overdue(): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $invoices = $this->salesInvoiceService->getOverdue($companyId);
        return ResponseHelper::success($invoices);
    }
}
