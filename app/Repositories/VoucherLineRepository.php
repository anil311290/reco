<?php

namespace App\Repositories;

use App\Interfaces\VoucherLineRepositoryInterface;
use App\Models\VoucherLine;
use Illuminate\Database\Eloquent\Collection;

class VoucherLineRepository extends BaseRepository implements VoucherLineRepositoryInterface
{
    public function __construct(VoucherLine $model)
    {
        parent::__construct($model);
    }

    /**
     * Get lines by voucher
     */
    public function getByVoucher(int $voucherId): Collection
    {
        return $this->model->where('voucher_id', $voucherId)->get();
    }

    /**
     * Get lines by account
     */
    public function getByAccount(int $accountId): Collection
    {
        return $this->model->where('account_id', $accountId)->get();
    }

    /**
     * Delete lines by voucher
     */
    public function deleteByVoucher(int $voucherId): bool
    {
        return $this->model->where('voucher_id', $voucherId)->delete() > 0;
    }
}