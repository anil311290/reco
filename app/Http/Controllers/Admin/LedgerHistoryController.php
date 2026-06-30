<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ledger;
use App\Services\LedgerPartyHistoryService;
use Illuminate\Http\Request;

class LedgerHistoryController extends Controller
{
    protected LedgerPartyHistoryService $historyService;

    public function __construct(LedgerPartyHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    public function show(Request $request, int $ledgerId)
    {
        $ledger = Ledger::findOrFail($ledgerId);

        $history = $this->historyService->getLedgerHistory($ledgerId);

        return view('admin.ledgers.history', compact('ledger', 'history'));
    }
}
