<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartyRequest;
use App\Services\LedgerService;
use App\Services\PartyService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    protected PartyService $partyService;
    protected LedgerService $ledgerService;

    public function __construct(PartyService $partyService, LedgerService $ledgerService)
    {
        $this->partyService = $partyService;
        $this->ledgerService = $ledgerService;
    }

    /**
     * Show party detail with its ledger transaction history.
     */
    public function show(Request $request, int $party)
    {
        $id = $party;
        $partyModel = $this->partyService->getById($id);

        if (!$partyModel || $partyModel->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Party not found');
        }

        $financialYearId = $request->user()->company->currentFinancialYear?->id;
        $perPage = (int) $request->input('per_page', 15);

        $ledger = $this->ledgerService->getPartyLedger(
            $id,
            $request->user()->company_id,
            $financialYearId,
            $request->input('date_from'),
            $request->input('date_to'),
            $perPage > 0 ? $perPage : 15
        );

        $party = $partyModel;

        return view('admin.parties.show', compact('party', 'ledger'));
    }

    /**
     * Display parties list
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = [];
            $filters['company_id'] = $request->user()->company_id;
            if ($request->filled('type')) $filters['type'] = $request->input('type');
            if ($request->filled('is_active')) $filters['is_active'] = $request->input('is_active');
            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            
            $parties = $this->partyService->getPaginated($filters);

            return response()->json([
                'data' => $parties->items(),
                'recordsTotal' => $parties->total(),
                'recordsFiltered' => $parties->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.parties.index');
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.parties.create');
    }

    /**
     * Store new party
     */
    public function store(PartyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $request->user()->company_id;
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;
            $data['created_by_ip'] = request()->ip();
            $data['updated_by_ip'] = request()->ip();

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

                return ResponseHelper::success($party, 'Party restored successfully');
            }

            unset($data['duplicate_action']);
            $party = $this->partyService->create($data);

            return ResponseHelper::success($party, 'Party created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(int $id)
    {
        $party = $this->partyService->getById($id);

        if (!$party) {
            return ResponseHelper::notFound('Party not found');
        }

        $typeLocked = $this->partyService->isTransactionallyUsed($party->id);

        return view('admin.parties.edit', compact('party', 'typeLocked'));
    }

    /**
     * Update party
     */
    public function update(PartyRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;
            $data['updated_by_ip'] = request()->ip();

            $updated = $this->partyService->update($id, $data);

            if (!$updated) {
                return ResponseHelper::notFound('Party not found');
            }

            return ResponseHelper::success(null, 'Party updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete party
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->partyService->delete($id);

            if (!$deleted) {
                return ResponseHelper::notFound('Party not found');
            }

            return ResponseHelper::success(null, 'Party deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Change party status
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        try {
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

    /**
     * Get parties by type (for AJAX)
     */
    public function getByType(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:debtor,creditor',
        ]);

        $companyId = $request->user()->company_id;
        $parties = $this->partyService->getForDropdown($companyId, $request->type);

        return response()->json($parties);
    }

    /**
     * Export party ledger to Excel.
     */
    public function exportExcel(Request $request, int $party)
    {
        $partyModel = $this->partyService->getById($party);

        if (!$partyModel || $partyModel->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Party not found');
        }

        $financialYearId = $request->user()->company->currentFinancialYear?->id;

        $ledger = $this->ledgerService->getPartyLedger(
            $party,
            $request->user()->company_id,
            $financialYearId,
            $request->input('date_from'),
            $request->input('date_to'),
            0
        );

        $filename = 'party_ledger_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $partyModel->party_code) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PartyLedgerExport($ledger['rows']),
            $filename
        );
    }

    /**
     * Export party ledger to PDF.
     */
    public function exportPdf(Request $request, int $party)
    {
        $partyModel = $this->partyService->getById($party);

        if (!$partyModel || $partyModel->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Party not found');
        }

        $financialYearId = $request->user()->company->currentFinancialYear?->id;

        $ledger = $this->ledgerService->getPartyLedger(
            $party,
            $request->user()->company_id,
            $financialYearId,
            $request->input('date_from'),
            $request->input('date_to'),
            0
        );

        $filename = 'party_ledger_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $partyModel->party_code) . '_' . date('Y-m-d_H-i-s') . '.pdf';

        $party = $partyModel;

        $company = $request->user()->company;
        $exportMeta = [
            'company_name' => $company?->name ?? 'N/A',
            'financial_year' => $company?->currentFinancialYear?->name ?? 'All',
            'date_from' => $request->input('date_from') ?: 'N/A',
            'date_to' => $request->input('date_to') ?: 'N/A',
            'generated_by' => $request->user()->name ?? 'System',
            'generated_at' => now()->timezone(config('app.timezone'))->format('d-M-Y h:i A'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.parties.export-pdf', compact('party', 'ledger', 'exportMeta'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
