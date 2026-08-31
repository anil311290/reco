<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartyRequest;
use App\Http\Resources\PartyResource;
use App\Models\Party;
use App\Services\LedgerService;
use App\Services\PartyService;
use App\Services\PurchaseInvoiceService;
use App\Services\ReportService;
use App\Services\SalesInvoiceService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyApiController extends Controller
{
    protected PartyService $partyService;
    protected LedgerService $ledgerService;
    protected SalesInvoiceService $salesInvoiceService;
    protected PurchaseInvoiceService $purchaseInvoiceService;
    protected ReportService $reportService;
    protected \App\Http\Controllers\Admin\PartyController $partyAdmin;

    public function __construct(
        PartyService $partyService,
        LedgerService $ledgerService,
        SalesInvoiceService $salesInvoiceService,
        PurchaseInvoiceService $purchaseInvoiceService,
        ReportService $reportService,
        \App\Http\Controllers\Admin\PartyController $partyAdmin
    ) {
        $this->partyService = $partyService;
        $this->ledgerService = $ledgerService;
        $this->salesInvoiceService = $salesInvoiceService;
        $this->purchaseInvoiceService = $purchaseInvoiceService;
        $this->reportService = $reportService;
        $this->partyAdmin = $partyAdmin;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'type', 'is_active']);
        $filters['company_id'] = $request->user()->company_id;

        $parties = $this->partyService->getAll($filters);

        return ResponseHelper::success(
            PartyResource::collection($parties)
        );
    }

    public function show(int $id): JsonResponse
    {
        $party = $this->partyService->getById($id);

        if (!$party || $party->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Party not found');
        }

        return ResponseHelper::success(
            new PartyResource($party)
        );
    }

    /**
     * Party transaction history (ledger) mirroring the web party detail page.
     */
    public function history(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $party = $this->partyService->getById($id);

        if (!$party || $party->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Party not found');
        }

        $financialYearId = isset($validated['financial_year_id'])
            ? (int) $validated['financial_year_id']
            : $request->user()->company->currentFinancialYear?->id;

        $ledger = $this->ledgerService->getPartyLedger(
            $id,
            $party->company_id,
            $financialYearId,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null
        );

        $transactions = collect($ledger['rows'])->map(function (array $row) {
            $entry = $row['entry'];

            return [
                'date' => optional($entry->transaction_date)->toDateString(),
                'voucher_id' => $entry->voucher?->id,
                'voucher_number' => $entry->voucher?->voucher_number,
                'voucher_type' => $entry->voucher?->voucher_type,
                'description' => $entry->description ?: $entry->voucher?->narration,
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
                'running_balance' => $row['running_balance'],
                'running_type' => $row['running_type'],
            ];
        })->values();

        return ResponseHelper::success([
            'party' => new PartyResource($party),
            'total_debit' => $ledger['total_debit'],
            'total_credit' => $ledger['total_credit'],
            'closing_balance' => $ledger['closing_balance'],
            'closing_type' => $ledger['closing_type'],
            'transactions' => $transactions,
        ]);
    }

    public function store(PartyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $request->user()->company_id;
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;
            $data['created_by_ip'] = $request->ip();
            $data['updated_by_ip'] = $request->ip();

            $duplicateAction = $data['duplicate_action'] ?? null;
            $deletedParty = $this->partyService->findDeletedByName(
                $request->user()->company_id,
                $data['name']
            );

            if ($deletedParty && !$duplicateAction) {
                return response()->json([
                    'success' => false,
                    'code' => 'SOFT_DELETED_PARTY_EXISTS',
                    'message' => 'A deleted party with this name already exists.',
                    'data' => [
                        'party_code' => $deletedParty->party_code,
                        'party_name' => $deletedParty->name,
                    ],
                ], 409);
            }

            if ($deletedParty && $duplicateAction === 'restore') {
                $party = $this->partyService->restoreDeleted($deletedParty, $data);

                return ResponseHelper::success(
                    new PartyResource($party),
                    'Party restored successfully'
                );
            }

            unset($data['duplicate_action']);
            $party = $this->partyService->create($data);

            return ResponseHelper::success(
                new PartyResource($party),
                'Party created successfully',
                201
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(PartyRequest $request, int $id): JsonResponse
    {
        try {
            $party = $this->partyService->getById($id);

            if (!$party || $party->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Party not found');
            }

            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;
            $data['updated_by_ip'] = $request->ip();

            $updated = $this->partyService->update($id, $data);

            if (!$updated) {
                return ResponseHelper::notFound('Party not found');
            }

            return ResponseHelper::success(
                new PartyResource($this->partyService->getById($id)),
                'Party updated successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $party = $this->partyService->getById($id);

            if (!$party || $party->company_id !== request()->user()->company_id) {
                return ResponseHelper::notFound('Party not found');
            }

            $deleted = $this->partyService->delete($id);

            if (!$deleted) {
                return ResponseHelper::notFound('Party not found');
            }

            return ResponseHelper::success(null, 'Party deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        try {
            $party = $this->partyService->getById($id);

            if (!$party || $party->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Party not found');
            }

            $updated = $this->partyService->update($id, [
                'is_active' => $request->status,
            ]);

            if (!$updated) {
                return ResponseHelper::notFound('Party not found');
            }

            $statusText = $request->status ? 'activated' : 'deactivated';

            return ResponseHelper::success(null, "Party {$statusText} successfully");
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function getByType(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:debtor,creditor',
        ]);

        $companyId = $request->user()->company_id;
        $parties = $this->partyService->getForDropdown($companyId, $request->type);
        $nextPartyCode = Party::generateCode($request->type, $companyId);

        return ResponseHelper::success([
            'parties' => $parties,
            'next_party_code' => $nextPartyCode,
        ]);
    }

    /**
     * Open invoices for a party (used to build a payment allocation screen).
     */
    public function outstandingInvoices(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'invoice_type' => 'nullable|in:sales,purchase',
        ]);

        $party = $this->partyService->getById($id);

        if (! $party || $party->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Party not found');
        }

        return ResponseHelper::success(
            $this->partyService->getOutstandingInvoices($party, $request->input('invoice_type'))
        );
    }

    /**
     * Record one payment/receipt allocated across multiple invoices of a party.
     */
    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $party = $this->partyService->getById($id);

        if (! $party || $party->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Party not found');
        }

        if (! in_array($party->type, ['debtor', 'creditor'], true)) {
            return ResponseHelper::error('Party must be a debtor or creditor to record payment.');
        }

        $validated = $request->validate([
            'cash_bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_date' => ['required', 'date'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
            'allocations.*.reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $meta = [
            'company_id' => $party->company_id,
            'created_by' => $request->user()->id,
            'created_by_ip' => $request->ip(),
        ];

        try {
            $service = $party->type === 'debtor' ? $this->salesInvoiceService : $this->purchaseInvoiceService;

            $result = $service->recordMultiInvoicePayment(
                $party->id,
                $validated['allocations'],
                (int) $validated['cash_bank_account_id'],
                $validated['payment_date'],
                $meta
            );
        } catch (\RuntimeException $e) {
            return ResponseHelper::error($e->getMessage());
        }

        return ResponseHelper::success(
            ['voucher_number' => $result['voucher']->voucher_number],
            'Payment recorded against ' . $result['invoices']->count() . ' invoice(s).'
        );
    }

    /**
     * Apply a party's unapplied voucher amount (or opening balance) to one open invoice.
     * Mirrors admin.parties.apply-unapplied for app consumption.
     */
    public function applyUnapplied(Request $request, int $id): JsonResponse
    {
        return $this->partyAdmin->applyUnappliedAmount($request, $id);
    }
}
