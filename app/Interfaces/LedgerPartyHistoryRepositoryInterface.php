<?php

namespace App\Interfaces;

use App\Models\LedgerPartyHistory;
use Illuminate\Database\Eloquent\Collection;

interface LedgerPartyHistoryRepositoryInterface extends RepositoryInterface
{
    public function getByLedger(int $ledgerId): Collection;
    public function create(array $data): LedgerPartyHistory;
}
