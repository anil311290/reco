@extends('layouts.app')

@php
    $voucherLabels = [
        'income' => 'Sales',
        'expense' => 'Purchase',
        'payment' => 'Payments',
        'receipt' => 'Receipts',
        'journal' => 'Adjustments',
    ];
    $voucherLabel = $type ? ($voucherLabels[$type] ?? ucfirst($type)) : 'All';
@endphp

@section('title', $voucherLabel . ' Vouchers')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">{{ $voucherLabel }} Vouchers</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('vouchers.create')
        @if($type === 'payment')
            <a href="{{ route('admin.vouchers.create', 'payment') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add Payment Voucher
            </a>
        @elseif($type === 'receipt')
            <a href="{{ route('admin.vouchers.create', 'receipt') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add Receipt Voucher
            </a>
        @elseif(in_array($type, ['journal', 'adjustment'], true))
            <a href="{{ route('admin.vouchers.create', 'journal') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add Adjustment Voucher
            </a>
        @elseif($type === 'income')
            <a href="{{ route('admin.sales-invoices.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add Sales Invoice
            </a>
        @elseif($type === 'expense')
            <a href="{{ route('admin.purchase-invoices.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add Purchase Invoice
            </a>
        @else
            <div class="dropdown d-inline-block">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-plus-circle me-2"></i>Create Voucher
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Cash / Bank</h6></li>
                    <li><a class="dropdown-item" href="{{ route('admin.vouchers.create', 'payment') }}"><i class="bi bi-wallet2 me-2"></i>Payment</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.vouchers.create', 'receipt') }}"><i class="bi bi-cash-stack me-2"></i>Receipt</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">Journal</h6></li>
                    <li><a class="dropdown-item" href="{{ route('admin.vouchers.create', 'journal') }}"><i class="bi bi-journal-bookmark me-2"></i>Adjustment</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">Invoices</h6></li>
                    <li><a class="dropdown-item" href="{{ route('admin.sales-invoices.create') }}"><i class="bi bi-file-earmark-text me-2"></i>Sales Invoice</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.purchase-invoices.create') }}"><i class="bi bi-file-earmark-text me-2"></i>Purchase Invoice</a></li>
                </ul>
            </div>
        @endif
        @endpermission
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by number or narration...">
            </div>
            <div class="col-md-2">
                <label for="voucher_type" class="form-label">Type</label>
                <select class="form-select" id="voucher_type" name="voucher_type" {{ $type ? 'disabled' : '' }}>
                    <option value="">All Types</option>
                    <option value="payment" {{ $type === 'payment' ? 'selected' : '' }}>Payment</option>
                    <option value="receipt" {{ $type === 'receipt' ? 'selected' : '' }}>Receipt</option>
                    <option value="journal" {{ $type === 'journal' ? 'selected' : '' }}>Adjustment</option>
                    <option value="income" {{ $type === 'income' ? 'selected' : '' }}>Sales (Invoice posting)</option>
                    <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Purchase (Invoice posting)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="vouchersTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Voucher Number</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Party</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const table = loadDatatable('vouchersTable', '{{ $type ? route("admin.vouchers.type", $type) : route("admin.vouchers.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'voucher_number', name: 'voucher_number' },
        { 
            data: 'voucher_date',
            name: 'voucher_date',
            render: function(data) {
                return formatDateIst(data);
            }
        },
        { 
            data: 'voucher_type',
            name: 'voucher_type',
            render: function(data) {
                const badges = {
                    'income': 'bg-success',
                    'expense': 'bg-danger',
                    'receipt': 'bg-info',
                    'payment': 'bg-warning',
                    'journal': 'bg-primary',
                    'adjustment': 'bg-secondary'
                };
                const labels = {
                    income: 'Sales',
                    expense: 'Purchase',
                    receipt: 'Receipt',
                    payment: 'Payment',
                    journal: 'Adjustment'
                };
                return `<span class="badge ${badges[data] || 'bg-secondary'}">${labels[data] || (data.charAt(0).toUpperCase() + data.slice(1))}</span>`;
            }
        },
        { 
            data: 'party',
            name: 'party.name',
            render: function(data) {
                return data ? data.name : '<span class="text-muted">-</span>';
            }
        },
        { 
            data: 'total_debit',
            name: 'total_debit',
            render: function(data) {
                return formatCurrency(data || 0);
            }
        },
        {
            data: 'status',
            name: 'status',
            render: function(data) {
                const badges = {
                    'draft': 'bg-warning',
                    'posted': 'bg-success',
                    'cancelled': 'bg-danger'
                };
                return `<span class="badge ${badges[data] || 'bg-secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
            }
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data) {
                let actions = `
                    <div class="btn-group btn-group-sm">
                        <a href="/admin/vouchers/${data.id}" class="btn btn-outline-info" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                `;
                
                if (data.status === 'draft') {
                    actions += `
                        <a href="/admin/vouchers/${data.id}/edit" class="btn btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-outline-success post-btn" data-id="${data.id}" title="Post">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
                
                if (data.status === 'posted') {
                    actions += `
                        <button class="btn btn-outline-warning cancel-btn" data-id="${data.id}" title="Cancel">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    `;
                }
                
                actions += `</div>`;
                return actions;
            }
        }
    ], { order: [[2, 'desc']] });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Post button
    $('#vouchersTable').on('click', '.post-btn', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Post Voucher?',
            text: 'This will mark the voucher as posted and it cannot be edited.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Yes, post it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/vouchers/${id}/post`,
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            table.ajax.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'An error occurred');
                    }
                });
            }
        });
    });

    // Cancel button
    $('#vouchersTable').on('click', '.cancel-btn', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Cancel Voucher?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Yes, cancel it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/vouchers/${id}/cancel`,
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            table.ajax.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'An error occurred');
                    }
                });
            }
        });
    });

    // Delete button
    $('#vouchersTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        
        deleteRecord(`/admin/vouchers/${id}`, 'voucher', function() {
            table.ajax.reload();
        });
    });
});
</script>
@endpush
