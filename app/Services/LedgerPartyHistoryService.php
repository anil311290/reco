<?php

namespace App\Services;

use App\Interfaces\LedgerPartyHistoryRepositoryInterface;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\LedgerPartyHistory;
use Illuminate\Database\Eloquent\Collection;

class LedgerPartyHistoryService
{
    protected LedgerPartyHistoryRepositoryInterface $historyRepository;

    public function __construct(LedgerPartyHistoryRepositoryInterface $historyRepository)
    {
        $this->historyRepository = $historyRepository;
    }

    public function logHistory(Ledger $ledger, Party $party, string $referenceType = null, ?int $referenceId = null, ?string $notes = null): LedgerPartyHistory
    {
        return $this->historyRepository->create([
            'company_id' => $ledger->company_id,
            'financial_year_id' => $ledger->financial_year_id,
            'ledger_id' => $ledger->id,
            'party_id' => $party->id,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $ledger->created_by,
            'created_by_ip' => $ledger->created_by_ip,
        ]);
    }

    public function getLedgerHistory(int $ledgerId): Collection
    {
        return $this->historyRepository->getByLedger($ledgerId);
    }
}
