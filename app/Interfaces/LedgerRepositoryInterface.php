<?php

namespace App\Interfaces;

use App\Models\Ledger;
use Illuminate\Database\Eloquent\Collection;

interface LedgerRepositoryInterface extends RepositoryInterface
{
    public function deleteByVoucher(int $voucherId): void;

    public function getLastEntry(int $companyId, int $accountId, ?int $financialYearId = null): ?Ledger;

    public function getEntries(array $criteria = [], array $relations = []): Collection;
}
