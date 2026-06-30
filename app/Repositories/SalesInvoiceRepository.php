<?php

namespace App\Repositories;

use App\Interfaces\SalesInvoiceRepositoryInterface;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Collection;

class SalesInvoiceRepository extends BaseRepository implements SalesInvoiceRepositoryInterface
{
    public function __construct(SalesInvoice $model)
    {
        parent::__construct($model);
    }

    public function getOverdueByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->overdue()->get();
    }
}
