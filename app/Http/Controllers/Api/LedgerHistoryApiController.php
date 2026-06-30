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

        return ResponseHelper::success($history);
    }
}
