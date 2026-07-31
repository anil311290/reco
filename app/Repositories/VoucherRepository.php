<?php

namespace App\Repositories;

use App\Interfaces\VoucherRepositoryInterface;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;

class VoucherRepository extends BaseRepository implements VoucherRepositoryInterface
{
    public function __construct(Voucher $model)
    {
        parent::__construct($model);
    }

    /**
     * Find voucher by number within a company
     */
    public function findByNumber(string $number, int $companyId): ?Voucher
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('voucher_number', $number)
            ->first();
    }

    /**
     * Get vouchers by company
     */
    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    /**
     * Get vouchers by type
     */
    public function getByType(string $type, int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('voucher_type', $type)
            ->get();
    }

    /**
     * Get vouchers by status
     */
    public function getByStatus(string $status, int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('status', $status)
            ->get();
    }
}