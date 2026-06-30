<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Services\PurchaseInvoiceService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseInvoiceApiController extends Controller
{
    protected PurchaseInvoiceService $purchaseInvoiceService;

    public function __construct(PurchaseInvoiceService $purchaseInvoiceService)
    {
        $this->purchaseInvoiceService = $purchaseInvoiceService;
    }

    /**
     * Get all purchase invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $filters = $request->only(['search', 'status', 'party_id', 'date_from', 'date_to']);

        $perPage = $request->input('per_page', 15);
        $invoices = $this->purchaseInvoiceService->getPaginated($companyId, $filters, $perPage);

        return ResponseHelper::success([
            'data' => PurchaseInvoiceResource::collection($invoices->items()),
            'current_page' => $invoices->currentPage(),
            'last_page' => $invoices->lastPage(),
            'per_page' => $invoices->perPage(),
            'total' => $invoices->total(),
        ]);
    }

    /**
     * Get invoice by ID.
     */
    public function show(int $id): JsonResponse
    {
        $invoice = $this->purchaseInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        return ResponseHelper::success(new PurchaseInvoiceResource($invoice));
    }

    /**
     * Create purchase invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
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
        ]);

        $companyId = $request->user()->company_id;
        $fyId = $request->user()->company->currentFinancialYear?->id;

        $data = [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'company_id' => $companyId,
            'financial_year_id' => $fyId,
            'party_id' => $validated['party_id'],
            'invoice_number' => $this->purchaseInvoiceService->generateInvoiceNumber($companyId, $fyId),
            'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'status' => 'draft',
        ];

        $invoice = $this->purchaseInvoiceService->create($data, $validated['lines']);

        return ResponseHelper::success(new PurchaseInvoiceResource($invoice), 'Invoice created', 201);
    }

    /**
     * Record payment against invoice.
     */
    public function payment(Request $request, int $id): JsonResponse
    {
        $invoice = $this->purchaseInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $invoice = $this->purchaseInvoiceService->recordPayment($id, $validated['amount']);

        return ResponseHelper::success(new PurchaseInvoiceResource($invoice->fresh()), 'Payment recorded');
    }

    /**
     * Generate voucher from purchase invoice.
     */
    public function generateVoucher(int $id): JsonResponse
    {
        $invoice = $this->purchaseInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        if ($invoice->status === 'cancelled') {
            return ResponseHelper::error('Cannot generate voucher for cancelled invoice', 400);
        }

        $voucher = $this->purchaseInvoiceService->generateVoucher($invoice);

        if (!$voucher) {
            return ResponseHelper::error('Cannot generate voucher - missing required accounts', 400);
        }

        return ResponseHelper::success([
            'invoice' => new PurchaseInvoiceResource($invoice->fresh()),
            'voucher' => new \App\Http\Resources\VoucherResource($voucher),
        ], 'Voucher generated successfully');
    }
}
