<?php

namespace App\Interfaces;

use App\Models\PurchaseInvoice;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseInvoiceRepositoryInterface extends RepositoryInterface
{
    public function getOverdueByCompany(int $companyId): Collection;
}
