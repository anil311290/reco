<?php

namespace App\Repositories;

use App\Interfaces\LedgerPartyHistoryRepositoryInterface;
use App\Models\LedgerPartyHistory;
use Illuminate\Database\Eloquent\Collection;

class LedgerPartyHistoryRepository extends BaseRepository implements LedgerPartyHistoryRepositoryInterface
{
    public function __construct(LedgerPartyHistory $model)
    {
        parent::__construct($model);
    }

    public function create(array $data): LedgerPartyHistory
    {
        return parent::create($data);
    }

    public function getByLedger(int $ledgerId): Collection
    {
        return $this->model->where('ledger_id', $ledgerId)
            ->with(['party'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
