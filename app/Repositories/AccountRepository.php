<?php

namespace App\Repositories;

use App\Interfaces\AccountRepositoryInterface;
use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

class AccountRepository extends BaseRepository implements AccountRepositoryInterface
{
    public function __construct(Account $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code, ?int $companyId = null, ?int $financialYearId = null): ?Account
    {
        $query = $this->model->where('account_code', $code);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if ($financialYearId !== null) {
            $query->where('financial_year_id', $financialYearId);
        }

        $account = $query->first();

        if (!$account && $financialYearId !== null) {
            $fallback = $this->model->where('account_code', $code);
            if ($companyId !== null) {
                $fallback->where('company_id', $companyId);
            }
            return $fallback->first();
        }

        return $account;
    }

    public function getByType(string $type, int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->where('account_type', $type)->get();
    }

    public function getActiveByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->where('is_active', true)->get();
    }
}
