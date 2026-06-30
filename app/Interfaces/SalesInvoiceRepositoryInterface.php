<?php

namespace App\Interfaces;

use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Collection;

interface SalesInvoiceRepositoryInterface extends RepositoryInterface
{
    public function getOverdueByCompany(int $companyId): Collection;
}
