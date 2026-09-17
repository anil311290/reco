<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesInvoiceResource;
use App\Services\PartyService;
use App\Services\RecurringSalesInvoiceService;
use App\Services\SalesInvoiceService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SalesInvoiceApiController extends Controller
{
    protected SalesInvoiceService $salesInvoiceService;
    protected PartyService $partyService;
    protected RecurringSalesInvoiceService $recurringSalesInvoiceService;

    public function __construct(
        SalesInvoiceService $salesInvoiceService,
        PartyService $partyService,
        RecurringSalesInvoiceService $recurringSalesInvoiceService
    )
    {
        $this->salesInvoiceService = $salesInvoiceService;
        $this->partyService = $partyService;
        $this->recurringSalesInvoiceService = $recurringSalesInvoiceService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $filters = $request->only(['search', 'status', 'party_id', 'date_from', 'date_to']);
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

    public function show(int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        return ResponseHelper::success(new SalesInvoiceResource($invoice));
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $validated = $request->validate($this->salesInvoiceRules($companyId));

        $itemLines = $validated['lines'] ?? [];
        $serviceLines = $validated['service_lines'] ?? [];

        if (empty($itemLines) && empty($serviceLines)) {
            return ResponseHelper::error('Please add at least one item or service line', 422);
        }

        $fyId = $request->user()->company->currentFinancialYear?->id;
        if (!$fyId) {
            return ResponseHelper::error('No active financial year found. Cannot create invoice.', 422);
        }

        $resolvedSelection = $this->partyService->resolveInvoiceSelectionForPosting(
            $validated['party_id'],
            $companyId,
            'debtor'
        );

        // Auto-calculate due_date if not provided: invoice_date + 1 month
        $dueDate = $validated['due_date'] ?? date('Y-m-d', strtotime($validated['invoice_date'] . ' +1 month'));

        $data = [
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'financial_year_id' => $fyId,
            'party_id' => $resolvedSelection['party_id'],
            'account_id' => $resolvedSelection['account_id'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $dueDate,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'delivery_terms' => $validated['delivery_terms'] ?? null,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'status' => 'draft',
            'is_recurring' => $request->boolean('is_recurring'),
        ];

        $data = array_merge($data, $this->recurrenceData($validated, $request->boolean('is_recurring')));

        $invoice = $this->salesInvoiceService->create($data, $itemLines, $serviceLines);

        if ($request->boolean('save_as_draft')) {
            return ResponseHelper::success(new SalesInvoiceResource($invoice), 'Invoice saved as draft', 201);
        }

        $voucher = $this->salesInvoiceService->generateVoucher($invoice);

        if (!$voucher) {
            return ResponseHelper::error('Invoice created but voucher/journal posting failed. Please configure required accounts.', 400);
        }

        return ResponseHelper::success(new SalesInvoiceResource($invoice), 'Invoice created', 201);
    }

    public function payment(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $companyId) {
            return ResponseHelper::notFound('Invoice not found');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'cash_bank_account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'payment_date' => 'nullable|date',
        ]);

        try {
            $invoice = $this->salesInvoiceService->recordPayment($id, [
                'amount' => $validated['amount'],
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

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $companyId) {
            return ResponseHelper::notFound('Invoice not found');
        }

        $validated = $request->validate($this->salesInvoiceRules($companyId));

        $itemLines = $validated['lines'] ?? [];
        $serviceLines = $validated['service_lines'] ?? [];

        if (empty($itemLines) && empty($serviceLines)) {
            return ResponseHelper::error('Please add at least one item or service line', 422);
        }

        try {
            $resolvedSelection = $this->partyService->resolveInvoiceSelectionForPosting(
                $validated['party_id'],
                $companyId,
                'debtor'
            );

            // Auto-calculate due_date if invoice_date changed and due_date not explicitly provided
            $dueDate = $validated['due_date'];
            if (isset($validated['invoice_date']) && $validated['invoice_date'] !== $invoice->invoice_date->format('Y-m-d')) {
                if (!isset($validated['due_date']) || $validated['due_date'] === $invoice->due_date->format('Y-m-d')) {
                    $dueDate = date('Y-m-d', strtotime($validated['invoice_date'] . ' +1 month'));
                }
            }

            $data = [
                'party_id' => $resolvedSelection['party_id'],
                'account_id' => $resolvedSelection['account_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $dueDate,
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'delivery_terms' => $validated['delivery_terms'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'updated_by' => $request->user()->id,
                'updated_by_ip' => $request->ip(),
                'is_recurring' => $request->boolean('is_recurring'),
            ];

            $data = array_merge($data, $this->recurrenceData($validated, $request->boolean('is_recurring')));

            $invoice = $this->salesInvoiceService->updateWithLines(
                $id,
                $data,
                $itemLines,
                $serviceLines
            );

            if ($request->boolean('save_as_draft')) {
                // Ensure status is reset to draft when saving as draft
                if ($invoice->status !== 'draft') {
                    $invoice->update(['status' => 'draft']);
                }
            }

            return ResponseHelper::success(new SalesInvoiceResource($invoice), 'Invoice updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

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

    public function cancel(int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        try {
            $invoice = $this->salesInvoiceService->cancel($id);

            return ResponseHelper::success(new SalesInvoiceResource($invoice), 'Invoice cancelled successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Post a draft invoice to the ledger.
     */
    public function post(Request $request, int $id): JsonResponse
    {
        $invoice = $this->salesInvoiceService->getById($id);

        if (! $invoice || $invoice->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Sales invoice not found');
        }

        if ($invoice->status !== 'draft') {
            return ResponseHelper::error('Only draft invoices can be posted');
        }

        try {
            if (! $this->salesInvoiceService->generateVoucher($invoice)) {
                throw new \RuntimeException('Voucher/journal posting failed. Please check account mappings.');
            }
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }

        return ResponseHelper::success(
            new SalesInvoiceResource($invoice->fresh()),
            'Sales invoice posted successfully'
        );
    }

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
            Storage::put($path, $pdf);

            return ResponseHelper::success([
                'filename' => $filename,
                'content_type' => 'application/pdf',
                'content_base64' => base64_encode($pdf),
                'path' => Storage::url($path),
            ], 'PDF generated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function overdue(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $invoices = $this->salesInvoiceService->getOverdue($companyId);

        return ResponseHelper::success(SalesInvoiceResource::collection($invoices));
    }

    protected function salesInvoiceRules(int $companyId): array
    {
        return [
            'party_id' => ['required'],
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string|max:100',
            'is_recurring' => 'nullable|boolean',
            'recurrence_frequency' => 'required_if:is_recurring,1|nullable|in:weekly,monthly',
            'recurrence_day_of_week' => 'required_if:recurrence_frequency,weekly|nullable|integer|between:0,6',
            'recurrence_monthly_type' => 'required_if:recurrence_frequency,monthly|nullable|in:first_day,last_day,custom_day',
            'recurrence_day_of_month' => 'required_if:recurrence_monthly_type,custom_day|nullable|integer|between:1,31',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'required_without:service_lines|array|min:1',
            'lines.*.item_id' => [
                'required',
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
            'lines.*.quantity' => 'required_with:lines|numeric|min:0.001',
            'lines.*.unit_price' => 'required_with:lines|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'service_lines' => 'nullable|array',
            'service_lines.*.account_id' => [
                'required_with:service_lines',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->where('account_type', 'income')),
            ],
            'service_lines.*.tax_rate_id' => [
                'nullable',
                Rule::exists('tax_rates', 'id')->where('company_id', $companyId),
            ],
            'service_lines.*.description' => 'nullable|string',
            'service_lines.*.amount' => 'required_with:service_lines|numeric|min:0.01',
        ];
    }

    protected function recurrenceData(array $validated, bool $isRecurring): array
    {
        if (!$isRecurring) {
            return [
                'recurrence_frequency' => null,
                'recurrence_day_of_week' => null,
                'recurrence_monthly_type' => null,
                'recurrence_day_of_month' => null,
                'recurrence_next_run_at' => null,
                'recurrence_last_run_at' => null,
            ];
        }

        $invoiceDate = \Carbon\Carbon::parse($validated['invoice_date']);
        $frequency = $validated['recurrence_frequency'];
        $dayOfWeek = $frequency === 'weekly' ? $validated['recurrence_day_of_week'] : null;
        $monthlyType = $frequency === 'monthly' ? $validated['recurrence_monthly_type'] : null;
        $dayOfMonth = $monthlyType === 'custom_day' ? $validated['recurrence_day_of_month'] : null;

        return [
            'recurrence_frequency' => $frequency,
            'recurrence_day_of_week' => $dayOfWeek,
            'recurrence_monthly_type' => $monthlyType,
            'recurrence_day_of_month' => $dayOfMonth,
            'recurrence_next_run_at' => $this->recurringSalesInvoiceService
                ->initialNextRunDate($invoiceDate, $frequency, $dayOfWeek, $monthlyType, $dayOfMonth)
                ->toDateString(),
            'recurrence_last_run_at' => null,
        ];
    }
}
