@extends('layouts.app')

@section('title', 'Create Party')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Create New Party</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.parties.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Parties
        </a>
    </div>
</div>

<div class="card card-form">
    <div class="card-body">
        <form id="partyForm" method="POST" action="{{ route('admin.parties.store') }}">
            @csrf
            <input type="hidden" id="duplicate_action" name="duplicate_action" value="">
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="name" class="form-label">Party Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="{{ old('name') }}" required placeholder="e.g., ABC Company">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="type" class="form-label">Party Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="debtor" {{ old('type') === 'debtor' ? 'selected' : '' }}>Debtor (Customer)</option>
                        <option value="creditor" {{ old('type') === 'creditor' ? 'selected' : '' }}>Creditor (Supplier)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input type="text" class="form-control" id="mobile" name="mobile" 
                           value="{{ old('mobile') }}" placeholder="+91 9876543210">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="{{ old('email') }}" placeholder="party@example.com">
                </div>

                <div class="col-md-6">
                    <label for="gstin" class="form-label">GSTIN</label>
                    <input type="text" class="form-control" id="gstin" name="gstin" 
                           value="{{ old('gstin') }}" placeholder="22AAAAA0000A1Z5">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number" 
                           value="{{ old('pan_number') }}" placeholder="AAAAA1111A">
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                <textarea class="form-control" id="address" name="address" rows="2" 
                          placeholder="Enter full address" required>{{ old('address') }}</textarea>
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
                           value="{{ old('postal_code') }}" placeholder="400001">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="opening_balance" class="form-label">Opening Balance</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control" id="opening_balance" name="opening_balance" 
                               value="{{ old('opening_balance', '0.00') }}" step="0.01" min="0">
                    </div>
                    <div class="form-text">Cannot be edited after create.</div>
                </div>

                <div class="col-md-4">
                    <label for="opening_balance_type" class="form-label">Balance Type</label>
                    <select class="form-select" id="opening_balance_type" name="opening_balance_type">
                        <option value="debit" {{ old('opening_balance_type', 'debit') === 'debit' ? 'selected' : '' }}>Debit (DR)</option>
                        <option value="credit" {{ old('opening_balance_type') === 'credit' ? 'selected' : '' }}>Credit (CR)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="opening_date" class="form-label">Opening Date</label>
                    <input type="date" class="form-control" id="opening_date" name="opening_date" 
                           value="{{ old('opening_date', date('Y-m-d')) }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Notes</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="2"
                          placeholder="Enter any additional notes">{{ old('remarks') }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Create Party
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
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
                    citySelect.prop('disabled', false);
                }
            });
        } else {
            citySelect.prop('disabled', true);
        }
    });

    $('#name').on('input', function() {
        $('#duplicate_action').val('');
    });

    // Confirm opening balance before create — it cannot be edited later.
    $('#partyForm').on('submit.obConfirm', function(e) {
        const form = $(this);
        if (form.data('ob-confirmed')) {
            form.removeData('ob-confirmed');
            return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();

        const amount = parseFloat($('#opening_balance').val()) || 0;
        const balanceType = $('#opening_balance_type option:selected').text();
        const openingDate = $('#opening_date').val() || '-';
        const formattedAmount = amount.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        Swal.fire({
            title: 'Confirm Opening Balance',
            html: amount > 0
                ? `Opening balance of <strong>₹${formattedAmount}</strong> (${balanceType}) dated <strong>${openingDate}</strong> will be posted and <strong>cannot be edited later</strong>.`
                : `No opening balance will be posted. Opening balance <strong>cannot be set later</strong> after the party is created.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, create party',
            cancelButtonText: 'Review again'
        }).then(function(result) {
            if (result.isConfirmed) {
                form.data('ob-confirmed', true);
                form.trigger('submit');
            }
        });
    });

    ajaxFormSubmit(
        '#partyForm',
        '{{ route("admin.parties.store") }}',
        'POST',
        '{{ route("admin.parties.index") }}',
        function(xhr) {
            const response = xhr.responseJSON;
            if (xhr.status !== 409 || response?.code !== 'SOFT_DELETED_PARTY_EXISTS') {
                return false;
            }

            const deletedParty = response.data;
            Swal.fire({
                title: 'Deleted Party Found',
                text: `${deletedParty.party_name} (${deletedParty.party_code}) already exists in deleted records. Restore it or create a new party?`,
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#16a34a',
                denyButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Restore Party',
                denyButtonText: 'Create New Entry',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (!result.isConfirmed && !result.isDenied) {
                    return;
                }

                $('#duplicate_action').val(result.isConfirmed ? 'restore' : 'new_entry');
                $('#partyForm').data('ob-confirmed', true).trigger('submit');
            });

            return true;
        }
    );
});
</script>
@endpush

