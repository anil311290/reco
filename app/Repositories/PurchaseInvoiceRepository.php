<?php

namespace App\Repositories;

use App\Interfaces\PurchaseInvoiceRepositoryInterface;
use App\Models\PurchaseInvoice;
use Illuminate\Database\Eloquent\Collection;

class PurchaseInvoiceRepository extends BaseRepository implements PurchaseInvoiceRepositoryInterface
{
    public function __construct(PurchaseInvoice $model)
    {
        parent::__construct($model);
    }

    public function getOverdueByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->where('status', 'overdue')->get();
    }
}
