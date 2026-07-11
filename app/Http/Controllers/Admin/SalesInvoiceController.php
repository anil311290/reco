<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SalesInvoiceService;
use App\Services\PartyService;
use App\Services\AccountService;
use App\Services\ItemService;
use App\Services\TaxRateService;
use App\Services\ExportService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SalesInvoiceController extends Controller
{
    protected SalesInvoiceService $salesInvoiceService;
    protected PartyService $partyService;
    protected AccountService $accountService;
    protected ItemService $itemService;
    protected TaxRateService $taxRateService;
    protected ExportService $exportService;

    public function __construct(
        SalesInvoiceService $salesInvoiceService,
        PartyService $partyService,
        AccountService $accountService,
        ItemService $itemService,
        TaxRateService $taxRateService,
        ExportService $exportService
    ) {
        $this->salesInvoiceService = $salesInvoiceService;
        $this->partyService = $partyService;
        $this->accountService = $accountService;
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
            $filters = ['invoice_type' => 'item'];

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

        $parties = $this->partyService->getAll(['company_id' => $companyId, 'type' => 'debtor']);
        $items = $this->itemService->getAll($companyId);
        $taxRates = $this->taxRateService->getAll($companyId);
        $serviceAccounts = $this->accountService->getForDropdown($companyId, 'income');
        $invoiceNumber = $fyId ? $this->salesInvoiceService->generateInvoiceNumber($companyId, $fyId) : 'INV-000001';

        return view('admin.sales-invoices.create', compact('parties', 'items', 'taxRates', 'serviceAccounts', 'invoiceNumber'));
    }

    /**
     * Store new sales invoice.
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
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'service_lines' => 'nullable|array',
            'service_lines.*.account_id' => 'nullable|exists:accounts,id',
            'service_lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $companyId = auth()->user()->company_id;
            $fyId = auth()->user()->company->currentFinancialYear?->id;

            $data = [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'company_id' => $companyId,
                'financial_year_id' => $fyId,
                'party_id' => $validated['party_id'],
                'invoice_number' => $this->salesInvoiceService->generateInvoiceNumber($companyId, $fyId),
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
        return view('admin.sales-invoices.show', compact('invoice'));
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
        $companyId = auth()->user()->company_id;
        $parties = $this->partyService->getAll(['company_id' => $companyId, 'type' => 'debtor']);
        $items = $this->itemService->getAll($companyId);
        $taxRates = $this->taxRateService->getAll($companyId);
        $serviceAccounts = $this->accountService->getForDropdown($companyId, 'income');

        return view('admin.sales-invoices.edit', compact('invoice', 'parties', 'items', 'taxRates', 'serviceAccounts'));
    }

    /**
     * Update sales invoice.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'service_lines' => 'nullable|array',
            'service_lines.*.account_id' => 'nullable|exists:accounts,id',
            'service_lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $data = [
                'party_id' => $validated['party_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
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
        ]);

        try {
            $invoice = $this->salesInvoiceService->recordPayment($id, $validated['amount']);
            return ResponseHelper::success($invoice, 'Payment recorded successfully');
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
