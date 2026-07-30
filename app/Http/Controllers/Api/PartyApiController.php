<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartyRequest;
use App\Http\Resources\PartyResource;
use App\Services\LedgerService;
use App\Services\PartyService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyApiController extends Controller
{
    protected PartyService $partyService;
    protected LedgerService $ledgerService;

    public function __construct(PartyService $partyService, LedgerService $ledgerService)
    {
        $this->partyService = $partyService;
        $this->ledgerService = $ledgerService;
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

        return ResponseHelper::success($parties);
    }
}
