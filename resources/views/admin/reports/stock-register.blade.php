@extends('layouts.app')

@section('title', 'Stock Register')

@include('admin.reports._theme')

@push('styles')
<style>
    .stock-register-table {
        min-width: 1060px;
    }

    .stock-register-pagination {
        display: flex !important;
        min-height: 58px;
        visibility: visible !important;
    }

    .stock-register-page-size {
        display: inline-block !important;
        width: 76px;
        min-width: 76px;
        height: 34px;
        visibility: visible !important;
    }

    .stock-register-table .quantity-in {
        color: #15803d;
        font-weight: 700;
    }

    .stock-register-table .quantity-out {
        color: #b91c1c;
        font-weight: 700;
    }

    .stock-register-table .quantity-balance {
        color: #1d4ed8;
        font-weight: 800;
    }
</style>
@endpush

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-box-seam"></i> Inventory</span>
                <h1 class="report-title">Stock Register</h1>
                <p class="report-subtitle">Item-wise stock movement with opening quantity, inward quantity, outward quantity, and running balance.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft">
                    <i class="bi bi-arrow-left me-1"></i>Back to Accounting Reports
                </a>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <div class="report-filter-head">
            <span class="report-filter-head-title"><i class="bi bi-funnel"></i> Filters</span>
            <a href="{{ route('admin.reports.stock-register') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
        <form method="GET" action="{{ route('admin.reports.stock-register') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="item_id" class="form-label">Stock Item</label>
                <select id="item_id" name="item_id" class="form-select" required>
                    <option value="" disabled {{ empty($selectedItemId) ? 'selected' : '' }}>Select Stock Item</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ (string) $selectedItemId === (string) $item->id ? 'selected' : '' }}>
                            {{ $item->name }} ({{ $item->item_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-12 col-lg-auto report-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </form>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <h5 class="report-panel-title"><i class="bi bi-list-check text-primary"></i>Stock Movements</h5>
                <p class="mb-0 text-muted">Stock items are shown with their Stock ID in brackets.</p>
            </div>
            <span class="report-pill report-pill--info">{{ $totalMovements }} Movements</span>
        </div>
        <div class="report-table-tools justify-content-start">
            <form method="GET" action="{{ route('admin.reports.stock-register') }}" class="d-flex align-items-center gap-2 mb-0">
                <input type="hidden" name="item_id" value="{{ $selectedItemId ?? '' }}">
                <input type="hidden" name="from_date" value="{{ $fromDate ?? '' }}">
                <input type="hidden" name="to_date" value="{{ $toDate ?? '' }}">
                <label for="stock_per_page" class="report-rows-label">Rows Per Page</label>
                <select id="stock_per_page" name="stock_per_page" class="form-select form-select-sm stock-register-page-size" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (string) request('stock_per_page', 25) === (string) $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                    <option value="all" {{ strtolower((string) request('stock_per_page', 25)) === 'all' ? 'selected' : '' }}>All</option>
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table report-table stock-register-table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Stock Item</th>
                        <th>Movement</th>
                        <th>Reference</th>
                        <th>Party</th>
                        <th>UoM</th>
                        <th class="text-end">Quantity In</th>
                        <th class="text-end">Quantity Out</th>
                        <th class="text-end">Balance Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}</td>
                        <td class="fw-semibold">{{ $row['stock_reference'] }}</td>
                        <td>
                            <span class="report-pill {{ $row['type'] === 'purchase' ? 'report-pill--success' : ($row['type'] === 'sale' ? 'report-pill--danger' : 'report-pill--info') }}">
                                {{ $row['type_label'] }}
                            </span>
                        </td>
                        <td>
                            @if($row['invoice_route'] && $row['invoice_id'])
                                <a href="{{ route($row['invoice_route'], $row['invoice_id']) }}" class="report-detail-link">{{ $row['invoice_number'] }}</a>
                            @else
                                <span class="text-muted">Opening Stock</span>
                            @endif
                        </td>
                        <td>{{ $row['party_name'] ?: '-' }}</td>
                        <td>{{ $row['uom'] }}</td>
                        <td class="text-end quantity-in">{{ $row['qty_in'] > 0 ? number_format($row['qty_in'], 3) : '-' }}</td>
                        <td class="text-end quantity-out">{{ $row['qty_out'] > 0 ? number_format($row['qty_out'], 3) : '-' }}</td>
                        <td class="text-end quantity-balance">{{ number_format($row['running_qty'], 3) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">No stock movements found for the selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="fw-bold">Total</td>
                        <td class="text-end fw-bold quantity-in">{{ number_format($totalIn, 3) }}</td>
                        <td class="text-end fw-bold quantity-out">{{ number_format($totalOut, 3) }}</td>
                        <td class="text-end fw-bold quantity-balance">{{ number_format($closingQuantity, 3) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
            <div class="report-pagination stock-register-pagination px-3 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small">
                    Showing {{ $rows->firstItem() ?? 0 }} to {{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }} movements
                </span>
                @if($rows->hasPages())
                {{ $rows->onEachSide(1)->links('pagination::bootstrap-5') }}
                @endif
            </div>
    </div>
</div>
@endsection
