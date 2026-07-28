@extends('layouts.app')

@section('title', 'Edit Party')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Edit Party: {{ $party->name }}</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.parties.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Parties
        </a>
    </div>
</div>

<div class="card card-form">
    <div class="card-body">
        <form id="partyForm" method="POST" action="{{ route('admin.parties.update', $party->id) }}" data-ajax="true" data-success-redirect="{{ route('admin.parties.index') }}">
            @csrf
            @method('PUT')
            
            <input type="hidden" name="party_code" id="party_code" value="{{ old('party_code', $party->party_code) }}">
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="name" class="form-label">Party Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="{{ old('name', $party->name) }}" required>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="type" class="form-label">Party Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="type" name="type" required {{ !empty($typeLocked) ? 'disabled' : '' }}>
                        <option value="">Select Type</option>
                        <option value="debtor" {{ old('type', $party->type) === 'debtor' ? 'selected' : '' }}>Debtor (Customer)</option>
                        <option value="creditor" {{ old('type', $party->type) === 'creditor' ? 'selected' : '' }}>Creditor (Supplier)</option>
                    </select>
                    @if(!empty($typeLocked))
                        <input type="hidden" name="type" value="{{ $party->type }}">
                    @endif
                </div>

                <div class="col-md-6">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input type="text" class="form-control" id="mobile" name="mobile" 
                           value="{{ old('mobile', $party->mobile) }}">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="{{ old('email', $party->email) }}">
                </div>

                <div class="col-md-6">
                    <label for="gstin" class="form-label">GSTIN</label>
                    <input type="text" class="form-control" id="gstin" name="gstin" 
                           value="{{ old('gstin', $party->gstin) }}">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number" 
                           value="{{ old('pan_number', $party->pan_number) }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                <textarea class="form-control" id="address" name="address" rows="2" required>{{ old('address', $party->address) }}</textarea>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="state_id" class="form-label">State <span class="text-danger">*</span></label>
                    <select class="form-select" id="state_id" name="state_id" required>
                        <option value="">Select State</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="city_id" class="form-label">City <span class="text-danger">*</span></label>
                    <select class="form-select" id="city_id" name="city_id" disabled required>
                        <option value="">Select City</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="postal_code" class="form-label">Pincode <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="postal_code" name="postal_code" required
                           value="{{ old('postal_code', $party->postal_code) }}">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="opening_balance" class="form-label">Opening Balance</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control" id="opening_balance"
                               value="{{ old('opening_balance', $party->opening_balance) }}" step="0.01" min="0" readonly>
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="opening_balance_type" class="form-label">Balance Type</label>
                    <select class="form-select" id="opening_balance_type" disabled>
                        <option value="debit" {{ old('opening_balance_type', $party->opening_balance_type ?? 'debit') === 'debit' ? 'selected' : '' }}>Debit (DR)</option>
                        <option value="credit" {{ old('opening_balance_type', $party->opening_balance_type) === 'credit' ? 'selected' : '' }}>Credit (CR)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="opening_date" class="form-label">Opening Date</label>
                    <input type="date" class="form-control" id="opening_date"
                           value="{{ old('opening_date', $party->opening_date?->format('Y-m-d')) }}" readonly>
                </div>
            </div>

            <div class="alert alert-warning mt-3 mb-0">
                <i class="bi bi-shield-lock me-2"></i>
                Opening balance is locked after create and cannot be changed. Review carefully before saving a new party.
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Notes</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="2">{{ old('remarks', $party->remarks) }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           {{ old('is_active', $party->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Update Party
                </button>
            </div>
        </form>
    </div>
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
