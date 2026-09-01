<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Models\Item;
use App\Services\PartyService;
use App\Services\PurchaseInvoiceService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceApiController extends Controller
{
    protected PurchaseInvoiceService $purchaseInvoiceService;
    protected PartyService $partyService;

    public function __construct(PurchaseInvoiceService $purchaseInvoiceService, PartyService $partyService)
    {
        $this->purchaseInvoiceService = $purchaseInvoiceService;
        $this->partyService = $partyService;
    }

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

    public function show(int $id): JsonResponse
    {
        $invoice = $this->purchaseInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        return ResponseHelper::success(new PurchaseInvoiceResource($invoice));
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $validated = $request->validate($this->purchaseRules($companyId));
        $this->assertGoodsOnlyLines($companyId, $validated['lines']);

        $fyId = $request->user()->company->currentFinancialYear?->id;
        $resolvedSelection = $this->partyService->resolveInvoiceSelectionForPosting(
            $validated['party_id'],
            $companyId,
            'creditor'
        );

        // Auto-calculate due_date if not provided: invoice_date + 1 month
        $dueDate = $validated['due_date'] ?? date('Y-m-d', strtotime($validated['invoice_date'] . ' +1 month'));

        $data = [
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'financial_year_id' => $fyId,
            'party_id' => $resolvedSelection['party_id'],
            'account_id' => $resolvedSelection['account_id'],
            'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $dueDate,
            'notes' => $validated['notes'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'delivery_terms' => $validated['delivery_terms'] ?? null,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'status' => 'draft',
        ];

        $invoice = $this->purchaseInvoiceService->create($data, $validated['lines']);

        if ($request->boolean('save_as_draft')) {
            return ResponseHelper::success(new PurchaseInvoiceResource($invoice), 'Invoice saved as draft', 201);
        }

        $voucher = $this->purchaseInvoiceService->generateVoucher($invoice);
        if (!$voucher) {
            return ResponseHelper::error('Invoice created but accounting posting failed. Please configure required accounts.', 400);
        }

        return ResponseHelper::success(new PurchaseInvoiceResource($invoice), 'Invoice created', 201);
    }

    public function payment(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $invoice = $this->purchaseInvoiceService->getById($id);

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
            $invoice = $this->purchaseInvoiceService->recordPayment($id, [
                'amount' => $validated['amount'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                'created_by' => $request->user()->id,
                'created_by_ip' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }

        return ResponseHelper::success(new PurchaseInvoiceResource($invoice->fresh()), 'Payment recorded and payment voucher posted');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $invoice = $this->purchaseInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== $companyId) {
            return ResponseHelper::notFound('Invoice not found');
        }

        $validated = $request->validate($this->purchaseRules($companyId));
        $this->assertGoodsOnlyLines($companyId, $validated['lines']);

        try {
            $resolvedSelection = $this->partyService->resolveInvoiceSelectionForPosting(
                $validated['party_id'],
                $companyId,
                'creditor'
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
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $dueDate,
                'notes' => $validated['notes'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'delivery_terms' => $validated['delivery_terms'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'updated_by' => $request->user()->id,
                'updated_by_ip' => $request->ip(),
            ];

            $invoice = $this->purchaseInvoiceService->updateWithLines(
                $id,
                $data,
                $validated['lines']
            );

            if ($request->boolean('save_as_draft')) {
                // Ensure status is reset to draft when saving as draft
                if ($invoice->status !== 'draft') {
                    $invoice->update(['status' => 'draft']);
                }
            }

            return ResponseHelper::success(new PurchaseInvoiceResource($invoice), 'Invoice updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $invoice = $this->purchaseInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        try {
            $this->purchaseInvoiceService->delete($id);

            return ResponseHelper::success(null, 'Invoice deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $invoice = $this->purchaseInvoiceService->getById($id);

        if (!$invoice || $invoice->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Invoice not found');
        }

        try {
            $invoice = $this->purchaseInvoiceService->cancel($id);

            return ResponseHelper::success(new PurchaseInvoiceResource($invoice), 'Invoice cancelled successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Post a draft invoice to the ledger.
     */
    public function post(Request $request, int $id): JsonResponse
    {
        $invoice = $this->purchaseInvoiceService->getById($id);

        if (! $invoice || $invoice->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Purchase invoice not found');
        }

        if ($invoice->status !== 'draft') {
            return ResponseHelper::error('Only draft invoices can be posted');
        }

        try {
            if (! $this->purchaseInvoiceService->generateVoucher($invoice)) {
                throw new \RuntimeException('Voucher/journal posting failed. Please check account mappings.');
            }
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }

        return ResponseHelper::success(
            new PurchaseInvoiceResource($invoice->fresh()),
            'Purchase invoice posted successfully'
        );
    }

    protected function purchaseRules(int $companyId): array
    {
        return [
            'party_id' => ['required'],
            'supplier_invoice_number' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'required|array|min:1',
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
        ];
    }

    protected function assertGoodsOnlyLines(int $companyId, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $itemId = $line['item_id'] ?? null;
            if (!$itemId) {
                continue;
            }

            $item = Item::where('company_id', $companyId)->find($itemId);
            if (!$item || $item->type !== 'goods') {
                throw ValidationException::withMessages([
                    "lines.{$index}.item_id" => 'Purchase invoices only allow goods items.',
                ]);
            }
        }
    }
}
