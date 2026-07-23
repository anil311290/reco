<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Item;
use App\Models\PurchaseInvoiceLine;
use App\Models\SalesInvoiceLine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemService
{
    /**
     * Get all items for a company.
     */
    public function getAll(int $companyId, array $filters = []): Collection
    {
        $query = Item::with(['category', 'taxRate', 'incomeAccount', 'expenseAccount'])
            ->where('company_id', $companyId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('item_code', 'like', "%{$filters['search']}%")
                  ->orWhere('barcode', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * Get paginated items.
     */
    public function getPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Item::with(['category', 'taxRate'])
            ->where('company_id', $companyId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('item_code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Get item by ID.
     */
    public function getById(int $id): ?Item
    {
        return Item::with(['category', 'taxRate', 'incomeAccount', 'expenseAccount'])->find($id);
    }

    /**
     * Create an item.
     */
    public function create(array $data): Item
    {
        $data['current_stock'] = $data['opening_stock'] ?? 0;
        if (!array_key_exists('is_stockable', $data)) {
            $data['is_stockable'] = ($data['type'] ?? 'goods') === 'goods';
        }
        return Item::create($data);
    }

    /**
     * Update an item.
     */
    public function update(int $id, array $data): bool
    {
        return Item::findOrFail($id)->update($data);
    }

    /**
     * Delete an item.
     */
    public function delete(int $id): bool
    {
        return Item::findOrFail($id)->delete();
    }

    /**
     * Get low stock items.
     */
    public function getLowStock(int $companyId): Collection
    {
        return Item::where('company_id', $companyId)->lowStock()->get();
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(int $id): Item
    {
        $item = Item::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return $item;
    }

    /**
     * Sales line catalog: goods/service items + income accounts as services.
     * Matches admin sales invoice Items/Services dropdown.
     *
     * @return array{items: Collection, services: array<int, array<string, mixed>>}
     */
    public function getSalesLineCatalog(int $companyId, array $filters = []): array
    {
        $type = $filters['type'] ?? null;
        $items = new Collection();
        $services = [];

        if ($type !== 'service') {
            $itemFilters = $filters;
            if ($type === 'goods') {
                $itemFilters['type'] = 'goods';
            } else {
                unset($itemFilters['type']);
            }
            $items = $this->getAll($companyId, $itemFilters);
        }

        if ($type === null || $type === '' || $type === 'service') {
            $services = $this->getServiceAccountsForListing(
                $companyId,
                $filters['search'] ?? null,
                isset($filters['is_active']) ? (bool) $filters['is_active'] : true
            );
        }

        return [
            'items' => $items,
            'services' => $services,
        ];
    }

    /**
     * Active income accounts exposed as selectable service lines.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getServiceAccountsForListing(int $companyId, ?string $search = null, bool $activeOnly = true): array
    {
        $query = Account::query()
            ->where('company_id', $companyId)
            ->where('account_type', 'income');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                    ->orWhere('account_code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')
            ->get()
            ->map(static function (Account $account) {
                $text = "{$account->account_code} - {$account->account_name}";

                return [
                    'id' => $account->id,
                    'kind' => 'service',
                    'type' => 'service',
                    'account_code' => $account->account_code,
                    'name' => $account->account_name,
                    'text' => $text,
                    'description' => $text,
                    'account_type' => $account->account_type,
                    'selling_price' => 0,
                    'is_active' => (bool) $account->is_active,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Increase or decrease stock for a stockable goods item.
     * direction: in (purchase) | out (sale)
     */
    public function adjustStock(?int $itemId, float $quantity, string $direction): void
    {
        if (!$itemId || $quantity <= 0) {
            return;
        }

        $item = Item::query()->whereKey($itemId)->lockForUpdate()->first();
        if (!$item || $item->type !== 'goods' || $item->is_stockable === false) {
            return;
        }

        if ($direction === 'in') {
            $item->updateStock($quantity, 'add');
            return;
        }

        $item->updateStock($quantity, 'subtract');
    }

    /**
     * Apply stock for invoice item lines.
     * direction: in | out
     *
     * @param  iterable<int, array<string, mixed>|\App\Models\SalesInvoiceLine|\App\Models\PurchaseInvoiceLine>  $lines
     */
    public function applyStockFromLines(iterable $lines, string $direction): void
    {
        foreach ($lines as $line) {
            $lineType = is_array($line) ? ($line['line_type'] ?? 'item') : ($line->line_type ?? 'item');
            if ($lineType === 'service') {
                continue;
            }

            $itemId = is_array($line) ? ($line['item_id'] ?? null) : $line->item_id;
            $quantity = is_array($line) ? ($line['quantity'] ?? 0) : $line->quantity;

            $this->adjustStock($itemId ? (int) $itemId : null, (float) $quantity, $direction);
        }
    }

    /**
     * Stock + sales/purchase movement history for an item.
     *
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     total_in: float,
     *     total_out: float,
     *     total_sales_amount: float,
     *     total_purchase_amount: float,
     *     closing_qty: float,
     *     paginator: \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * }
     */
    public function getItemHistory(
        int $itemId,
        int $companyId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 15
    ): array {
        $item = Item::where('company_id', $companyId)->findOrFail($itemId);
        $rows = [];

        $openingQty = round((float) ($item->opening_stock ?? 0), 3);
        if ($item->type === 'goods') {
            $rows[] = [
                'date' => null,
                'type' => 'opening',
                'type_label' => 'Opening Stock',
                'invoice_id' => null,
                'invoice_number' => '-',
                'invoice_route' => null,
                'party_id' => null,
                'party_name' => null,
                'description' => 'Opening stock',
                'qty_in' => $openingQty,
                'qty_out' => 0.0,
                'rate' => 0.0,
                'tax_amount' => 0.0,
                'amount' => 0.0,
                'status' => null,
            ];
        }

        $salesQuery = SalesInvoiceLine::query()
            ->where('item_id', $itemId)
            ->whereHas('salesInvoice', function ($query) use ($companyId, $dateFrom, $dateTo) {
                $query->where('company_id', $companyId)
                    ->where('status', '!=', 'cancelled');
                if ($dateFrom) {
                    $query->whereDate('invoice_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('invoice_date', '<=', $dateTo);
                }
            })
            ->with(['salesInvoice.party']);

        foreach ($salesQuery->get() as $line) {
            $invoice = $line->salesInvoice;

            $rows[] = [
                'date' => optional($invoice->invoice_date)->toDateString(),
                'type' => 'sale',
                'type_label' => 'Sales',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_route' => 'admin.sales-invoices.show',
                'party_id' => $invoice->party_id,
                'party_name' => $invoice->party?->name,
                'description' => $line->description ?: $item->name,
                'qty_in' => 0.0,
                'qty_out' => round((float) $line->quantity, 3),
                'rate' => round((float) $line->unit_price, 2),
                'tax_amount' => round((float) $line->tax_amount, 2),
                'amount' => round((float) $line->total, 2),
                'status' => $invoice->status,
            ];
        }

        $purchaseQuery = PurchaseInvoiceLine::query()
            ->where('item_id', $itemId)
            ->whereHas('purchaseInvoice', function ($query) use ($companyId, $dateFrom, $dateTo) {
                $query->where('company_id', $companyId)
                    ->where('status', '!=', 'cancelled');
                if ($dateFrom) {
                    $query->whereDate('invoice_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('invoice_date', '<=', $dateTo);
                }
            })
            ->with(['purchaseInvoice.party']);

        foreach ($purchaseQuery->get() as $line) {
            $invoice = $line->purchaseInvoice;

            $rows[] = [
                'date' => optional($invoice->invoice_date)->toDateString(),
                'type' => 'purchase',
                'type_label' => 'Purchase',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_route' => 'admin.purchase-invoices.show',
                'party_id' => $invoice->party_id,
                'party_name' => $invoice->party?->name,
                'description' => $line->description ?: $item->name,
                'qty_in' => round((float) $line->quantity, 3),
                'qty_out' => 0.0,
                'rate' => round((float) $line->unit_price, 2),
                'tax_amount' => round((float) $line->tax_amount, 2),
                'amount' => round((float) $line->total, 2),
                'status' => $invoice->status,
            ];
        }

        usort($rows, function (array $a, array $b) {
            $dateA = $a['date'] ?? '0000-01-01';
            $dateB = $b['date'] ?? '0000-01-01';
            if ($dateA === $dateB) {
                $order = ['opening' => 0, 'purchase' => 1, 'sale' => 2];
                return ($order[$a['type']] ?? 9) <=> ($order[$b['type']] ?? 9);
            }
            return strcmp($dateA, $dateB);
        });

        $running = 0.0;
        $totalIn = 0.0;
        $totalOut = 0.0;
        $totalSalesAmount = 0.0;
        $totalPurchaseAmount = 0.0;
        $history = [];

        foreach ($rows as $row) {
            $running += $row['qty_in'] - $row['qty_out'];
            $totalIn += $row['qty_in'];
            $totalOut += $row['qty_out'];
            if ($row['type'] === 'sale') {
                $totalSalesAmount += $row['amount'];
            }
            if ($row['type'] === 'purchase') {
                $totalPurchaseAmount += $row['amount'];
            }
            $row['running_qty'] = round($running, 3);
            $history[] = $row;
        }

        $totals = [
            'rows' => $history,
            'total_in' => round($totalIn, 3),
            'total_out' => round($totalOut, 3),
            'total_sales_amount' => round($totalSalesAmount, 2),
            'total_purchase_amount' => round($totalPurchaseAmount, 2),
            'closing_qty' => round($running, 3),
            'paginator' => null,
        ];

        if ($perPage <= 0) {
            return $totals;
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = count($history);
        $pageItems = array_slice($history, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        $totals['rows'] = $pageItems;
        $totals['paginator'] = $paginator;

        return $totals;
    }
}
