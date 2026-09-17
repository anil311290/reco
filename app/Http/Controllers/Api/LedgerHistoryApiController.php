<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LedgerPartyHistoryService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LedgerHistoryApiController extends Controller
{
    protected LedgerPartyHistoryService $historyService;

    public function __construct(LedgerPartyHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    public function index(Request $request, int $ledgerId): JsonResponse
    {
        $history = $this->historyService->getLedgerHistory($ledgerId);

        $rows = $history->map(function ($item) {
            $referenceType = $item->reference_type ?? '-';
            $referenceId = $item->reference_id;

            $referenceLabel = $referenceId
                ? "$referenceType #$referenceId"
                : (string) $referenceType;

            $createdBy = $item->created_by;
            if ($createdBy && is_numeric($createdBy)) {
                $name = \App\Models\User::query()->whereKey((int) $createdBy)->value('name');
                if ($name) {
                    $createdBy = $name;
                }
            }

            return [
                'id' => $item->id,
                'created_at' => optional($item->created_at)->toISOString(),
                'party' => $item->party
                    ? [
                        'id' => $item->party->id,
                        'name' => $item->party->name,
                    ]
                    : null,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_label' => $referenceLabel,
                'notes' => $item->notes,
                'created_by' => $createdBy ?: 'System',
            ];
        })->values();

        return ResponseHelper::success($rows);
    }
}
