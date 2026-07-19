@extends('layouts.app')

@section('title', 'Party Details')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">{{ $party->name }}</h4>
        <small class="text-muted">{{ $party->party_code }} &middot; {{ ucfirst($party->type) }}</small>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.parties.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Parties
        </a>
        @permission('parties.edit')
        <a href="{{ route('admin.parties.edit', $party->id) }}" class="btn btn-primary ms-2">
            <i class="bi bi-pencil me-2"></i>Edit Party
        </a>
        @endpermission
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Party Details</h5>
                <div class="mb-3">
                    <div class="text-muted small">Name</div>
                    <div>{{ $party->name }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Code</div>
                    <div>{{ $party->party_code }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Type</div>
                    <div>{{ ucfirst($party->type) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Mobile</div>
                    <div>{{ $party->mobile ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Email</div>
                    <div>{{ $party->email ?: '-' }}</div>
                </div>
                <div class="mb-0">
                    <div class="text-muted small">Closing Balance</div>
                    <div class="fw-semibold">
                        &#8377; {{ number_format($ledger['closing_balance'], 2) }}
                        <span class="badge bg-{{ $ledger['closing_type'] === 'debit' ? 'primary' : 'success' }}">
                            {{ strtoupper($ledger['closing_type']) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Transaction History</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Voucher #</th>
                                <th>Particulars</th>
                                <th class="text-end">Debit (&#8377;)</th>
                                <th class="text-end">Credit (&#8377;)</th>
                                <th class="text-end">Balance (&#8377;)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ledger['rows'] as $row)
                                @php($entry = $row['entry'])
                                <tr>
                                    <td>@istDate($entry->transaction_date)</td>
                                    <td>
                                        @if($entry->voucher)
                                            <a href="{{ route('admin.vouchers.show', $entry->voucher->id) }}" class="report-detail-link" title="View voucher">
                                                {{ $entry->voucher->voucher_number }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ Str::limit($entry->description ?: ($entry->voucher->narration ?? '-'), 60) }}</td>
                                    <td class="text-end">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}</td>
                                    <td class="text-end">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}</td>
                                    <td class="text-end">
                                        {{ number_format($row['running_balance'], 2) }}
                                        <small class="text-muted">{{ strtoupper($row['running_type']) }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No transactions found for this party.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($ledger['rows']) > 0)
                        <tfoot>
                            <tr class="fw-semibold">
                                <td colspan="3" class="text-end">Total</td>
                                <td class="text-end">{{ number_format($ledger['total_debit'], 2) }}</td>
                                <td class="text-end">{{ number_format($ledger['total_credit'], 2) }}</td>
                                <td class="text-end">
                                    {{ number_format($ledger['closing_balance'], 2) }}
                                    <small class="text-muted">{{ strtoupper($ledger['closing_type']) }}</small>
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
