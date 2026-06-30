<?php

namespace App\Repositories;

use App\Interfaces\LedgerRepositoryInterface;
use App\Models\Ledger;
use Illuminate\Database\Eloquent\Collection;

class LedgerRepository extends BaseRepository implements LedgerRepositoryInterface
{
    public function __construct(Ledger $model)
    {
        parent::__construct($model);
    }

    public function deleteByVoucher(int $voucherId): void
    {
        $this->model->where('voucher_id', $voucherId)->delete();
    }

    public function getLastEntry(int $companyId, int $accountId): ?Ledger
    {
        return $this->model->where('company_id', $companyId)
            ->where('account_id', $accountId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getEntries(array $criteria = [], array $relations = []): Collection
    {
        $query = $this->model->with($relations);

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get();
    }
}
