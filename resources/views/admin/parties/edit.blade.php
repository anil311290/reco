@extends('layouts.app')

@section('title', 'Edit Party')

@section('content')
<div class="party-shell">
    <div class="account-hero p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="account-kicker mb-3"><i class="bi bi-pencil-square"></i> Party Master</span>
                <h1 class="h2 fw-bold mb-2">Edit {{ $party->name }}</h1>
                <p class="mb-0 account-hero-copy">Update contact, tax, address, and status details for {{ $party->party_code }}.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2 me-2">{{ $party->party_code }}</span>
                <a href="{{ route('admin.parties.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>

    <form id="partyForm" method="POST" action="{{ route('admin.parties.update', $party->id) }}" data-ajax="true" data-success-redirect="{{ route('admin.parties.index') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="party_code" id="party_code" value="{{ old('party_code', $party->party_code) }}">

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card account-section-card">
                    <div class="card-header">
                        <h5 class="mb-1">Party Details</h5>
                        <p class="mb-0 account-note">Update editable information below.</p>
                    </div>
                    <div class="card-body">
                        <section class="party-form-section">
                            <div class="party-section-heading">
                                <span class="party-section-icon"><i class="bi bi-person-vcard"></i></span>
                                <div><h6 class="mb-0">Identity & Contact</h6><small class="text-muted">Basic party and communication details</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="name" class="form-label fw-semibold">Party Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name"
                                        value="{{ old('name', $party->name) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label fw-semibold">Party Type <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-lg" id="type" name="type" required {{ !empty($typeLocked) ? 'disabled' : '' }}>
                                        <option value="">Select Type</option>
                                        <option value="debtor" {{ old('type', $party->type) === 'debtor' ? 'selected' : '' }}>Customer</option>
                                        <option value="creditor" {{ old('type', $party->type) === 'creditor' ? 'selected' : '' }}>Supplier</option>
                                    </select>
                                    @if(!empty($typeLocked))
                                        <input type="hidden" name="type" value="{{ $party->type }}">
                                        <div class="form-text"><i class="bi bi-lock me-1"></i>Locked because transactions exist.</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label for="mobile" class="form-label fw-semibold">Mobile</label>
                                    <input type="text" class="form-control" id="mobile" name="mobile"
                                        value="{{ old('mobile', $party->mobile) }}" placeholder="+91 9876543210">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email', $party->email) }}" placeholder="party@example.com">
                                </div>
                            </div>
                        </section>

                        <section class="party-form-section">
                            <div class="party-section-heading">
                                <span class="party-section-icon"><i class="bi bi-receipt"></i></span>
                                <div><h6 class="mb-0">Tax Information</h6><small class="text-muted">Optional statutory registration details</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="gstin" class="form-label fw-semibold">GSTIN</label>
                                    <input type="text" class="form-control text-uppercase" id="gstin" name="gstin"
                                        value="{{ old('gstin', $party->gstin) }}" placeholder="22AAAAA0000A1Z5">
                                </div>
                                <div class="col-md-6">
                                    <label for="pan_number" class="form-label fw-semibold">PAN Number</label>
                                    <input type="text" class="form-control text-uppercase" id="pan_number" name="pan_number"
                                        value="{{ old('pan_number', $party->pan_number) }}" placeholder="AAAAA1111A">
                                </div>
                            </div>
                        </section>

                        <section class="party-form-section">
                            <div class="party-section-heading">
                                <span class="party-section-icon"><i class="bi bi-geo-alt"></i></span>
                                <div><h6 class="mb-0">Billing Address</h6><small class="text-muted">Used on invoices and party documents</small></div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="address" name="address" rows="3" required>{{ old('address', $party->address) }}</textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="state_id" class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                                    <select class="form-select" id="state_id" name="state_id" required><option value="">Select State</option></select>
                                </div>
                                <div class="col-md-4">
                                    <label for="city_id" class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                                    <select class="form-select" id="city_id" name="city_id" disabled required><option value="">Select City</option></select>
                                </div>
                                <div class="col-md-4">
                                    <label for="postal_code" class="form-label fw-semibold">Pincode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required
                                        value="{{ old('postal_code', $party->postal_code) }}">
                                </div>
                            </div>
                        </section>

                        <section class="party-form-section">
                            <div class="party-section-heading">
                                <span class="party-section-icon party-section-icon-locked"><i class="bi bi-shield-lock"></i></span>
                                <div><h6 class="mb-0">Opening Balance</h6><small class="text-muted">Locked financial values</small></div>
                            </div>
                            <div class="party-opening-panel party-opening-panel-locked">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="opening_balance" class="form-label fw-semibold">Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" id="opening_balance"
                                                value="{{ old('opening_balance', $party->opening_balance) }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="opening_balance_type" class="form-label fw-semibold">Balance Type</label>
                                        <select class="form-select" id="opening_balance_type" disabled>
                                            <option value="debit" {{ old('opening_balance_type', $party->opening_balance_type ?? 'debit') === 'debit' ? 'selected' : '' }}>Debit (DR)</option>
                                            <option value="credit" {{ old('opening_balance_type', $party->opening_balance_type) === 'credit' ? 'selected' : '' }}>Credit (CR)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-text mt-2"><i class="bi bi-lock me-1"></i>Opening balance cannot be edited after creation.</div>
                            </div>
                        </section>

                        <section class="party-form-section">
                            <label for="remarks" class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                placeholder="Add internal notes about this party">{{ old('remarks', $party->remarks) }}</textarea>
                        </section>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card account-side-card mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Party Summary</h6>
                        <div class="account-stat mb-3">
                            <p class="account-stat-label">Party Code</p>
                            <p class="account-stat-value">{{ $party->party_code }}</p>
                        </div>
                        <div class="account-stat mb-3">
                            <p class="account-stat-label">Current Type</p>
                            <p class="account-stat-value">{{ $party->type === 'debtor' ? 'Customer' : 'Supplier' }}</p>
                        </div>
                        <div class="account-stat">
                            <p class="account-stat-label">Opening Balance</p>
                            <p class="account-stat-value">₹{{ number_format((float) $party->opening_balance, 2) }} @drCr($party->opening_balance_type ?? 'debit')</p>
                        </div>
                    </div>
                </div>

                <div class="card account-side-card">
                    <div class="card-body">
                        <div class="form-check form-switch fs-5 mb-4">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                {{ old('is_active', $party->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">Active party</label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Update Party
                            </button>
                            <a href="{{ route('admin.parties.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const currentStateId = {{ $party->state_id ?? 'null' }};
    const currentCityId = {{ $party->city_id ?? 'null' }};
    
    // Load states
    $.ajax({
        url: '/api/v1/states',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            const stateSelect = $('#state_id');
            data.forEach(function(state) {
                stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
            });
            
            if (currentStateId) {
                stateSelect.val(currentStateId).trigger('change');
            }
        }
    });

    // Load cities when state changes
    $('#state_id').change(function() {
        const stateId = $(this).val();
        const citySelect = $('#city_id');
        citySelect.html('<option value="">Select City</option>');
        
        if (stateId) {
            $.ajax({
                url: `/api/v1/states/${stateId}/cities`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    data.forEach(function(city) {
                        citySelect.append(`<option value="${city.id}">${city.name}</option>`);
                    });
                    if (currentCityId) {
                        citySelect.val(currentCityId);
                    }
                    citySelect.prop('disabled', false);
                }
            });
        } else {
            citySelect.prop('disabled', true);
        }
    });
});
</script>
@endpush
