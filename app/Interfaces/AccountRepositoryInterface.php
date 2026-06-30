<?php

namespace App\Interfaces;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

interface AccountRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code, ?int $companyId = null, ?int $financialYearId = null): ?Account;

    public function getByType(string $type, int $companyId): Collection;

    public function getActiveByCompany(int $companyId): Collection;
}
