@extends('layouts.app')

@section('title', 'Item Details')

@section('content')
<style>
    .item-history-table {
        --bs-table-bg: transparent;
        margin-bottom: 0;
        font-size: 0.875rem;
    }
    .item-history-table thead th {
        background: #f8f9fa;
        color: #495057;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        white-space: nowrap;
        border-bottom-width: 1px;
        padding: 0.65rem 0.75rem;
    }
    .item-history-table tbody td,
    .item-history-table tfoot td {
        padding: 0.65rem 0.75rem;
        vertical-align: middle;
        white-space: nowrap;
    }
    .item-history-table tbody td.party-cell {
        white-space: normal;
        max-width: 180px;
    }
    .item-history-table tfoot tr {
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }
    .item-history-table .badge {
        font-weight: 500;
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }
    .item-history-table .tabular-nums {
        font-variant-numeric: tabular-nums;
    }
    .item-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem 1.25rem;
    }
    .item-summary-grid .label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0.15rem;
    }
    .item-summary-grid .value {
        font-size: 0.9375rem;
        color: #212529;
        font-weight: 500;
    }
</style>

<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">{{ $item->name }}</h4>
        <small class="text-muted">{{ $item->item_code }} &middot; {{ ucfirst($item->type) }}</small>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.items.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Items
        </a>
        @permission('accounts.edit')
        <a href="{{ route('admin.items.edit', $item->id) }}" class="btn btn-primary ms-2">
            <i class="bi bi-pencil me-2"></i>Edit Item
        </a>
        @endpermission
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Item Details</h5>
        <div class="item-summary-grid">
            <div>
                <div class="label">Code</div>
                <div class="value">{{ $item->item_code }}</div>
            </div>
            <div>
                <div class="label">Type</div>
                <div class="value">{{ ucfirst($item->type) }}</div>
            </div>
            <div>
                <div class="label">Category</div>
                <div class="value">{{ $item->category?->name ?: '-' }}</div>
            </div>
            <div>
                <div class="label">HSN / SAC</div>
                <div class="value">{{ $item->hsn_sac_code ?: '-' }}</div>
            </div>
            @if($item->type === 'goods')
            <div>
                <div class="label">Unit</div>
                <div class="value">{{ $item->unit ?: '-' }}</div>
            </div>
            @endif
            <div>
                <div class="label">Purchase Price</div>
                <div class="value">&#8377; {{ number_format((float) $item->purchase_price, 2) }}</div>
            </div>
            <div>
                <div class="label">Selling Price</div>
                <div class="value">&#8377; {{ number_format((float) $item->selling_price, 2) }}</div>
            </div>
            <div>
                <div class="label">Tax Rate</div>
                <div class="value">{{ $item->taxRate?->name ?: '-' }}</div>
            </div>
            <div>
                <div class="label">Status</div>
                <div class="value">
                    <span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">
                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            @if($item->type === 'goods')
            <div>
                <div class="label">Opening Stock</div>
                <div class="value">{{ number_format((float) $item->opening_stock, 3) }} {{ $item->unit }}</div>
            </div>
            <div>
                <div class="label">Current Stock</div>
                <div class="value fw-semibold">{{ number_format((float) $item->current_stock, 3) }} {{ $item->unit }}</div>
            </div>
            @endif
            <div>
                <div class="label">Total Purchases</div>
                <div class="value">&#8377; {{ number_format($history['total_purchase_amount'], 2) }}</div>
            </div>
            <div>
                <div class="label">Total Sales</div>
                <div class="value">&#8377; {{ number_format($history['total_sales_amount'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="card-title mb-0">Stock &amp; Transaction History</h5>
                <small class="text-muted">
                    Qty In {{ number_format($history['total_in'], 3) }}
                    &middot; Qty Out {{ number_format($history['total_out'], 3) }}
                    &middot; Closing {{ number_format($history['closing_qty'], 3) }}
                </small>
            </div>
            <div class="btn-group" role="group">
                <a href="{{ route('admin.items.export-excel', array_filter(['id' => $item->id, 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
                   class="btn btn-success btn-sm" title="Export to Excel">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
                <a href="{{ route('admin.items.export-pdf', array_filter(['id' => $item->id, 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
                   class="btn btn-danger btn-sm" title="Export to PDF">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover item-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Invoice #</th>
                        <th>Party</th>
                        <th class="text-end">Qty In</th>
                        <th class="text-end">Qty Out</th>
                        <th class="text-end">Rate (&#8377;)</th>
                        <th class="text-end">Amount (&#8377;)</th>
                        <th class="text-end">Balance Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history['rows'] as $row)
                        <tr>
                            <td class="text-muted">
                                @if($row['date'])
                                    @istDate($row['date'])
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $row['type'] === 'sale' ? 'primary' : ($row['type'] === 'purchase' ? 'success' : 'secondary') }}">
                                    {{ $row['type_label'] }}
                                </span>
                            </td>
                            <td>
                                @if($row['invoice_id'] && $row['invoice_route'])
                                    <a href="{{ route($row['invoice_route'], $row['invoice_id']) }}" class="report-detail-link" title="View invoice">
                                        {{ $row['invoice_number'] }}
                                    </a>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="party-cell">
                                @if($row['party_id'])
                                    <a href="{{ route('admin.parties.show', $row['party_id']) }}" class="report-detail-link" title="{{ $row['party_name'] }}">
                                        {{ $row['party_name'] }}
                                    </a>
                                @else
                                    <span class="text-muted">{{ $row['party_name'] ?: '—' }}</span>
                                @endif
                            </td>
                            <td class="text-end tabular-nums">{{ $row['qty_in'] > 0 ? number_format($row['qty_in'], 3) : '—' }}</td>
                            <td class="text-end tabular-nums">{{ $row['qty_out'] > 0 ? number_format($row['qty_out'], 3) : '—' }}</td>
                            <td class="text-end tabular-nums">{{ $row['rate'] > 0 ? number_format($row['rate'], 2) : '—' }}</td>
                            <td class="text-end tabular-nums">{{ $row['amount'] > 0 ? number_format($row['amount'], 2) : '—' }}</td>
                            <td class="text-end fw-semibold tabular-nums">{{ number_format($row['running_qty'], 3) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No transactions found for this item.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($history['rows']) > 0)
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="4" class="text-end text-muted">Total</td>
                        <td class="text-end tabular-nums">{{ number_format($history['total_in'], 3) }}</td>
                        <td class="text-end tabular-nums">{{ number_format($history['total_out'], 3) }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-end tabular-nums">{{ number_format($history['closing_qty'], 3) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($history['paginator'])
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted">
                Showing {{ $history['paginator']->firstItem() ?? 0 }}
                to {{ $history['paginator']->lastItem() ?? 0 }}
                of {{ $history['paginator']->total() }} entries
            </small>
            @if($history['paginator']->hasPages())
            <div>
                {{ $history['paginator']->withQueryString()->links() }}
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
