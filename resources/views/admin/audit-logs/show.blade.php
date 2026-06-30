@extends('layouts.app')

@section('title', 'Audit Log Detail')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <span class="badge bg-secondary mb-2">Audit Log</span>
        <h4 class="mb-1">Audit Record #{{ $log->id }}</h4>
        <p class="text-muted mb-0">Detailed comparison of the record changes captured in this audit entry.</p>
    </div>
    <div class="col-md-4 text-md-end align-self-center">
        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Audit Logs
        </a>
    </div>
</div>

<div class="row gy-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <small class="text-uppercase text-muted">Action</small>
                            <h6 class="mt-2">{{ $log->action_label }}</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <small class="text-uppercase text-muted">Module</small>
                            <h6 class="mt-2">{{ ucfirst($log->module) }}</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <small class="text-uppercase text-muted">Performed by</small>
                            <h6 class="mt-2">{{ $log->user?->name ?? 'System' }}</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <small class="text-uppercase text-muted">Record ID</small>
                            <h6 class="mt-2">{{ $log->record_id ?? '-' }}</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <small class="text-uppercase text-muted">Date</small>
                            <h6 class="mt-2">{{ $log->created_at->format('d M Y H:i:s') }}</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <small class="text-uppercase text-muted">IP Address</small>
                            <h6 class="mt-2">{{ $log->ip_address ?? '-' }}</h6>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <small class="text-uppercase text-muted">Description</small>
                            <p class="mb-0 mt-2 text-secondary">{{ $log->description ?? 'No description provided.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="row gy-4">
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Old Values</h6>
                    </div>
                    <div class="card-body">
                        @if(!empty($log->old_values) && is_array($log->old_values))
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="w-25">Field</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($log->old_values as $field => $value)
                                            <tr>
                                                <td class="text-muted align-middle">{{ $field }}</td>
                                                <td class="text-break">
                                                    @if(is_array($value))
                                                        <pre class="mb-0 small">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    @else
                                                        {{ $value === null ? '-' : $value }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No previous values recorded.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">New Values</h6>
                    </div>
                    <div class="card-body">
                        @if(!empty($log->new_values) && is_array($log->new_values))
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="w-25">Field</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($log->new_values as $field => $value)
                                            <tr>
                                                <td class="text-muted align-middle">{{ $field }}</td>
                                                <td class="text-break">
                                                    @if(is_array($value))
                                                        <pre class="mb-0 small">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    @else
                                                        {{ $value === null ? '-' : $value }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No updated values recorded.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
