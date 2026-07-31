<?php

namespace App\Interfaces;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;

interface VoucherRepositoryInterface extends RepositoryInterface
{
    /**
     * Find voucher by number
     */
    public function findByNumber(string $number, int $companyId): ?Voucher;

    /**
     * Get vouchers by company
     */
    public function getByCompany(int $companyId): Collection;

    /**
     * Get vouchers by type
     */
    public function getByType(string $type, int $companyId): Collection;

    /**
     * Get vouchers by status
     */
    public function getByStatus(string $status, int $companyId): Collection;
}