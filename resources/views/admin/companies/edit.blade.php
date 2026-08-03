@extends('layouts.app')

@section('title', 'Edit Company')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="mb-1">Edit Company</h2>
            <p class="text-muted mb-0">Update tenant profile details and platform status.</p>
        </div>
        <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Companies
        </a>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <span class="badge rounded-pill {{ $company->is_active ? 'text-bg-success-subtle text-success' : 'text-bg-danger-subtle text-danger' }} mb-3">
                        {{ $company->is_active ? 'Active Tenant' : 'Inactive Tenant' }}
                    </span>
                    <h4 class="mb-1">{{ $company->name }}</h4>
                    <p class="text-muted mb-4">{{ $company->email ?: 'No email provided' }}</p>

                    <div class="small text-muted d-grid gap-3">
                        <div>
                            <div class="text-uppercase fw-semibold mb-1">Phone</div>
                            <div class="text-dark">{{ $company->phone ?: '-' }}</div>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold mb-1">Registered On</div>
                            <div class="text-dark">{{ optional($company->created_at)->format('d-M-Y h:i A') ?: '-' }}</div>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold mb-1">City</div>
                            <div class="text-dark">{{ $company->city ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-1">Company Details</h5>
                    <p class="text-muted small mb-0">Only platform operators can change these values.</p>
                </div>
                <div class="card-body p-4 pt-3">
                    <form id="companyForm" method="POST" action="{{ route('admin.companies.update', $company->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Company Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ $company->name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ $company->email }}">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="{{ $company->phone }}">
                            </div>
                            <div class="col-md-6">
                                <label for="is_active" class="form-label">Status</label>
                                <select id="is_active" name="is_active" class="form-select">
                                    <option value="1" {{ $company->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !$company->is_active ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-2"></i>Save Changes
                            </button>
                            <a href="{{ route('admin.companies.index') }}" class="btn btn-light border">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
ajaxFormSubmit('#companyForm', '{{ route("admin.companies.update", $company->id) }}', 'PUT', '{{ route("admin.companies.index") }}');
</script>
@endsection
