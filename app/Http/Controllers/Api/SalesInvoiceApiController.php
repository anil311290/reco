<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesInvoiceResource;
use App\Services\SalesInvoiceService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesInvoiceApiController extends Controller
{
    protected SalesInvoiceService $salesInvoiceService;

    public function __construct(SalesInvoiceService $salesInvoiceService)
    {
        $this->salesInvoiceService = $salesInvoiceService;
    }

    /**
     * Get all sales invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $filters = $request->only(['search', 'status', 'party_id', 'date_from', 'date_to', 'invoice_type']);
        if (empty($filters['invoice_type'])) {
            $filters['invoice_type'] = 'item'; // default to item invoices
        }

        $perPage = $request->input('per_page', 15);
        $invoices = $this->salesInvoiceService->getPaginated($companyId, $filters, $perPage);

        return ResponseHelper::success([
            'data' => SalesInvoiceResource::collection($invoices->items()),
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
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        return ResponseHelper::success(new SalesInvoiceResource($invoice));
    }

    /**
     * Create sales invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string|max:100',
            'invoice_type' => 'nullable|in:item,service',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'required_without:service_lines|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required_with:lines|numeric|min:0.001',
            'lines.*.unit_price' => 'required_with:lines|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'service_lines' => 'required_without:lines|array|min:1',
            'service_lines.*.account_id' => 'nullable|exists:accounts,id',
            'service_lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'required_with:service_lines|numeric|min:0',
        ]);

        $invoiceType = $validated['invoice_type'] ?? 'item';
        $itemLines = $validated['lines'] ?? [];
        $serviceLines = $validated['service_lines'] ?? [];

        if ($invoiceType === 'service' && empty($serviceLines) && !empty($itemLines)) {
            $serviceLines = array_map(static function (array $line) {
                return [
                    'account_id' => $line['account_id'] ?? null,
                    'tax_rate_id' => $line['tax_rate_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'amount' => (float) ($line['quantity'] ?? 1) * (float) ($line['unit_price'] ?? 0),
                ];
            }, $itemLines);
            $itemLines = [];
        }

        $companyId = $request->user()->company_id;
        $fyId = $request->user()->company->currentFinancialYear?->id;

        $data = [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'company_id' => $companyId,
            'financial_year_id' => $fyId,
            'party_id' => $validated['party_id'],
            'invoice_type' => $invoiceType,
            'invoice_number' => $this->salesInvoiceService->generateInvoiceNumber($companyId, $fyId, $invoiceType),
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'delivery_terms' => $validated['delivery_terms'] ?? null,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'status' => 'draft',
        ];

        $invoice = $this->salesInvoiceService->create($data, $itemLines, $serviceLines);
        $voucher = $this->salesInvoiceService->generateVoucher($invoice);

        if (!$voucher) {
            return ResponseHelper::error('Invoice created but voucher/journal posting failed. Please configure required accounts.', 400);
        }

        return ResponseHelper::success(new SalesInvoiceResource($invoice), 'Invoice created', 201);
    }

    /**
     * Record payment against invoice.
     */
    public function payment(Request $request, int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $request->user()->company_id) {
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
                'created_by' => $request->user()->id,
                'created_by_ip' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }

        return ResponseHelper::success(new SalesInvoiceResource($invoice->fresh()), 'Payment recorded and receipt voucher posted');
    }

    /**
     * Update sales invoice.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'required_without:service_lines|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required_with:lines|numeric|min:0.001',
            'lines.*.unit_price' => 'required_with:lines|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'service_lines' => 'required_without:lines|array|min:1',
            'service_lines.*.account_id' => 'nullable|exists:accounts,id',
            'service_lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'required_with:service_lines|numeric|min:0',
        ]);

        try {
            $data = [
                'party_id' => $validated['party_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'updated_by' => $request->user()->id,
                'updated_by_ip' => $request->ip(),
            ];

            $invoice = $this->salesInvoiceService->updateWithLines(
                $id,
                $data,
                $validated['lines'] ?? [],
                $validated['service_lines'] ?? []
            );

            return ResponseHelper::success(new SalesInvoiceResource($invoice), 'Invoice updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete sales invoice.
     */
    public function destroy(int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        try {
            $this->salesInvoiceService->delete($id);

            return ResponseHelper::success(null, 'Invoice deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Export sales invoice PDF.
     */
    public function exportPdf(int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        try {
            $pdf = app(\App\Services\ExportService::class)->exportSalesInvoicePdf($id);
            $filename = 'sales-invoice-' . $id . '-' . date('Y-m-d') . '.pdf';
            $path = "exports/{$filename}";

            \Illuminate\Support\Facades\Storage::put($path, $pdf);

            return ResponseHelper::success([
                'filename' => $filename,
                'path' => \Illuminate\Support\Facades\Storage::url($path),
                'download_url' => url('api/v1/download/' . base64_encode($path)),
            ], 'PDF generated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get overdue invoices.
     */
    public function overdue(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $invoices = $this->salesInvoiceService->getOverdue($companyId);

        return ResponseHelper::success(SalesInvoiceResource::collection($invoices));
    }

    public function indexService(Request $request): JsonResponse
    {
        $request->merge(['invoice_type' => 'service']);

        return $this->index($request);
    }

    public function storeService(Request $request): JsonResponse
    {
        $request->merge(['invoice_type' => 'service']);

        return $this->store($request);
    }

    public function showService(int $id): JsonResponse
    {
        return $this->showTypedService($id);
    }

    public function updateService(Request $request, int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $request->user()->company_id || $invoice->invoice_type !== 'service') {
            return ResponseHelper::notFound('Service invoice not found');
        }

        return $this->update($request, $id);
    }

    public function destroyService(int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id || $invoice->invoice_type !== 'service') {
            return ResponseHelper::notFound('Service invoice not found');
        }

        return $this->destroy($id);
    }

    public function paymentService(Request $request, int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $request->user()->company_id || $invoice->invoice_type !== 'service') {
            return ResponseHelper::notFound('Service invoice not found');
        }

        return $this->payment($request, $id);
    }

    protected function showTypedService(int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id || $invoice->invoice_type !== 'service') {
            return ResponseHelper::notFound('Service invoice not found');
        }

        return ResponseHelper::success(new SalesInvoiceResource($invoice));
    }
}
