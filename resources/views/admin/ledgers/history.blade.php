@extends('layouts.app')

@section('title', 'Ledger History')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-clock-history"></i> Ledger History</span>
                <h1 class="report-title">Ledger {{ $ledger->id }} History</h1>
                <p class="report-subtitle">View related party association history for this ledger entry.</p>
            </div>
            <div class="col-lg-4 text-end">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>
    </div>

    <div class="report-panel mt-4">
        <div class="report-panel-header">
            <h6 class="report-panel-title">History for Ledger Entry #{{ $ledger->id }}</h6>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Party</th>
                        <th>Reference</th>
                        <th>Notes</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $item)
                    <tr>
                        <td>@istDateTime($item->created_at)</td>
                        <td>{{ $item->party->name ?? 'N/A' }}</td>
                        <td>{{ $item->reference_type }} #{{ $item->reference_id }}</td>
                        <td>{{ $item->notes ?? '-' }}</td>
                        <td>{{ $item->created_by ?? 'System' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-3">No history found for this ledger entry.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
