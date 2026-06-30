<?php

namespace App\Interfaces;

use App\Models\VoucherLine;
use Illuminate\Database\Eloquent\Collection;

interface VoucherLineRepositoryInterface extends RepositoryInterface
{
    /**
     * Get lines by voucher
     */
    public function getByVoucher(int $voucherId): Collection;

    /**
     * Get lines by account
     */
    public function getByAccount(int $accountId): Collection;

    /**
     * Delete lines by voucher
     */
    public function deleteByVoucher(int $voucherId): bool;
}