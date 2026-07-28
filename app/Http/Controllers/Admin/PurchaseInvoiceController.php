<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\PurchaseInvoiceService;
use App\Services\PartyService;
use App\Services\AccountService;
use App\Services\ItemService;
use App\Services\TaxRateService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceController extends Controller
{
    protected PurchaseInvoiceService $purchaseInvoiceService;
    protected PartyService $partyService;
    protected AccountService $accountService;
    protected ItemService $itemService;
    protected TaxRateService $taxRateService;

    public function __construct(
        PurchaseInvoiceService $purchaseInvoiceService,
        PartyService $partyService,
        AccountService $accountService,
        ItemService $itemService,
        TaxRateService $taxRateService
    ) {
        $this->purchaseInvoiceService = $purchaseInvoiceService;
        $this->partyService = $partyService;
        $this->accountService = $accountService;
        $this->itemService = $itemService;
        $this->taxRateService = $taxRateService;
    }

    /**
     * Display purchase invoices list.
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
            $invoices = $this->purchaseInvoiceService->getPaginated($companyId, $filters, (int) $perPage);

            return response()->json([
                'data' => $invoices->items(),
                'recordsTotal' => $invoices->total(),
                'recordsFiltered' => $invoices->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.purchase-invoices.index');
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $companyId = auth()->user()->company_id;
        $fyId = auth()->user()->company->currentFinancialYear?->id;

        $parties = $this->partyService->getAll(['company_id' => $companyId, 'type' => 'creditor']);
        $items = $this->itemService->getAll($companyId, ['type' => 'goods', 'is_active' => true]);
        $taxRates = $this->taxRateService->getAll($companyId);
        $invoiceNumber = $fyId ? $this->purchaseInvoiceService->generateInvoiceNumber($companyId, $fyId) : null;

        return view('admin.purchase-invoices.create', compact('parties', 'items', 'taxRates', 'invoiceNumber'));
    }

    /**
     * Store new purchase invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
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
                Rule::exists('items', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->where('type', 'goods')),
            ],
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $this->assertGoodsOnlyLines($companyId, $validated['lines']);
            $fyId = auth()->user()->company->currentFinancialYear?->id;

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
                'payment_terms' => $validated['payment_terms'] ?? null,
                'delivery_terms' => $validated['delivery_terms'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ];

            $invoice = $this->purchaseInvoiceService->create($data, $validated['lines']);

            $voucher = $this->purchaseInvoiceService->generateVoucher($invoice);
            if (!$voucher) {
                throw new \RuntimeException('Purchase invoice created but accounting posting failed. Please check account mappings.');
            }

            return ResponseHelper::success($invoice, 'Purchase invoice created successfully');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show invoice details.
     */
    public function show(int $id)
    {
        $invoice = $this->purchaseInvoiceService->getById($id);
        $companyId = auth()->user()->company_id;
        $financialYearId = auth()->user()->company->currentFinancialYear?->id;
        $cashBankAccounts = $this->accountService->getCashBankAccountsForMode($companyId, null, $financialYearId);

        return view('admin.purchase-invoices.show', compact('invoice', 'cashBankAccounts'));
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $invoice = $this->purchaseInvoiceService->getById($id);
        if (!$invoice) {
            abort(404);
        }
        if (in_array($invoice->status, ['paid', 'partial', 'cancelled'], true)) {
            return redirect()
                ->route('admin.purchase-invoices.show', $id)
                ->with('error', 'Paid, partially paid, or cancelled invoices cannot be edited.');
        }

        $companyId = auth()->user()->company_id;
        $parties = $this->partyService->getAll(['company_id' => $companyId, 'type' => 'creditor']);
        $items = $this->itemService->getAll($companyId, ['type' => 'goods', 'is_active' => true]);
        $taxRates = $this->taxRateService->getAll($companyId);

        return view('admin.purchase-invoices.edit', compact('invoice', 'parties', 'items', 'taxRates'));
    }

    /**
     * Update purchase invoice.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
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
                Rule::exists('items', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->where('type', 'goods')),
            ],
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $this->assertGoodsOnlyLines($companyId, $validated['lines']);

            $data = [
                'party_id' => $validated['party_id'],
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'delivery_terms' => $validated['delivery_terms'] ?? null,
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'updated_by' => auth()->id(),
                'updated_by_ip' => $request->ip(),
            ];

            $invoice = $this->purchaseInvoiceService->updateWithLines($id, $data, $validated['lines']);
            return ResponseHelper::success($invoice, 'Purchase invoice updated successfully');
        } catch (ValidationException $e) {
            throw $e;
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
            'payment_mode' => 'required|in:cash,bank,od',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'payment_date' => 'nullable|date',
        ]);

        try {
            $invoice = $this->purchaseInvoiceService->recordPayment($id, [
                'amount' => $validated['amount'],
                'payment_mode' => $validated['payment_mode'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                'created_by' => auth()->id(),
                'created_by_ip' => $request->ip(),
            ]);
            return ResponseHelper::success($invoice, 'Payment recorded and payment voucher posted');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Cancel purchase invoice (reverses vouchers, ledger, and stock).
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $invoice = $this->purchaseInvoiceService->getById($id);
            if (!$invoice || $invoice->company_id !== auth()->user()->company_id) {
                return ResponseHelper::notFound('Purchase invoice not found');
            }

            $invoice = $this->purchaseInvoiceService->cancel($id);
            return ResponseHelper::success($invoice, 'Purchase invoice cancelled successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete purchase invoice.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->purchaseInvoiceService->delete($id);
            return ResponseHelper::success(null, 'Purchase invoice deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
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
                    "lines.{$index}.item_id" => 'Purchase invoices only allow goods items. Services cannot be purchased.',
                ]);
            }
        }
    }
}
