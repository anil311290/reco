@extends('layouts.app')

@section('title', 'Create Party')

@php
    $openingDateDefault = auth()->user()->company->currentFinancialYear?->start_date?->format('Y-m-d') ?? date('Y-m-d');
@endphp

@section('content')
<div class="party-shell">
    <div class="account-hero p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="account-kicker mb-3"><i class="bi bi-people"></i> Party Master</span>
                <h1 class="h2 fw-bold mb-2">Create a new customer or supplier</h1>
                <p class="mb-0 account-hero-copy">Add contact, tax, address, and opening balance details in one place.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="#partyFormCard" class="btn btn-primary btn-lg me-2">
                    <i class="bi bi-arrow-down me-1"></i>Start Form
                </a>
                <a href="{{ route('admin.parties.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>

    <form id="partyForm" method="POST" action="{{ route('admin.parties.store') }}">
        @csrf
        <input type="hidden" id="duplicate_action" name="duplicate_action" value="">

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card account-section-card" id="partyFormCard">
                    <div class="card-header">
                        <h5 class="mb-1">Party Details</h5>
                        <p class="mb-0 account-note">Fields marked with * are required.</p>
                    </div>
                    <div class="card-body">
                        <section class="party-form-section">
                            <div class="party-section-heading">
                                <span class="party-section-icon"><i class="bi bi-person-vcard"></i></span>
                                <div>
                                    <h6 class="mb-0">Identity & Contact</h6>
                                    <small class="text-muted">Basic party and communication details</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="name" class="form-label fw-semibold">Party Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name"
                                        value="{{ old('name') }}" required placeholder="e.g., ABC Company">
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label fw-semibold">Party Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="debtor" {{ old('type') === 'debtor' ? 'selected' : '' }}>Customer</option>
                                        <option value="creditor" {{ old('type') === 'creditor' ? 'selected' : '' }}>Supplier</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="mobile" class="form-label fw-semibold">Mobile</label>
                                    <input type="text" class="form-control" id="mobile" name="mobile"
                                        value="{{ old('mobile') }}" placeholder="+91 9876543210">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" placeholder="party@example.com">
                                </div>
                            </div>
                        </section>

                        <section class="party-form-section">
                            <div class="party-section-heading">
                                <span class="party-section-icon"><i class="bi bi-receipt"></i></span>
                                <div>
                                    <h6 class="mb-0">Tax Information</h6>
                                    <small class="text-muted">Optional statutory registration details</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="gstin" class="form-label fw-semibold">GSTIN</label>
                                    <input type="text" class="form-control text-uppercase" id="gstin" name="gstin"
                                        value="{{ old('gstin') }}" placeholder="22AAAAA0000A1Z5">
                                </div>
                                <div class="col-md-6">
                                    <label for="pan_number" class="form-label fw-semibold">PAN Number</label>
                                    <input type="text" class="form-control text-uppercase" id="pan_number" name="pan_number"
                                        value="{{ old('pan_number') }}" placeholder="AAAAA1111A">
                                </div>
                            </div>
                        </section>

                        <section class="party-form-section">
                            <div class="party-section-heading">
                                <span class="party-section-icon"><i class="bi bi-geo-alt"></i></span>
                                <div>
                                    <h6 class="mb-0">Billing Address</h6>
                                    <small class="text-muted">Used on invoices and party documents</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="address" name="address" rows="3"
                                    placeholder="Enter complete billing address" required>{{ old('address') }}</textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="state_id" class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                                    <select class="form-select" id="state_id" name="state_id" required>
                                        <option value="">Select State</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="city_id" class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                                    <select class="form-select" id="city_id" name="city_id" disabled required>
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="postal_code" class="form-label fw-semibold">Pincode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required
                                        value="{{ old('postal_code') }}" placeholder="400001">
                                </div>
                            </div>
                        </section>

                        <section class="party-form-section">
                            <div class="party-section-heading">
                                <span class="party-section-icon"><i class="bi bi-wallet2"></i></span>
                                <div>
                                    <h6 class="mb-0">Opening Balance</h6>
                                    <small class="text-muted">Set once when creating the party</small>
                                </div>
                            </div>
                            <div class="party-opening-panel">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label for="opening_balance" class="form-label fw-semibold">Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" id="opening_balance" name="opening_balance"
                                                value="{{ old('opening_balance', '0.00') }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="opening_balance_type" class="form-label fw-semibold">Balance Type</label>
                                        <select class="form-select" id="opening_balance_type" name="opening_balance_type">
                                            <option value="debit" {{ old('opening_balance_type', 'debit') === 'debit' ? 'selected' : '' }}>Debit (DR)</option>
                                            <option value="credit" {{ old('opening_balance_type') === 'credit' ? 'selected' : '' }}>Credit (CR)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-text mt-2"><i class="bi bi-lock me-1"></i>Opening balance cannot be changed after creation. Opening date is auto-set to current financial year start date: <strong>{{ $openingDateDefault }}</strong>.</div>
                            </div>
                        </section>

                        <section class="party-form-section">
                            <label for="remarks" class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                placeholder="Add internal notes about this party">{{ old('remarks') }}</textarea>
                        </section>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card account-side-card mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Party Setup</h6>
                        <div class="account-stat mb-3">
                            <p class="account-stat-label">Customer</p>
                            <p class="account-stat-value">Money receivable</p>
                            <div class="small text-muted">Choose Customer when the party normally owes your business.</div>
                        </div>
                        <div class="account-stat mb-3">
                            <p class="account-stat-label">Supplier</p>
                            <p class="account-stat-value">Money payable</p>
                            <div class="small text-muted">Choose Supplier when your business normally owes the party.</div>
                        </div>
                        <div class="account-stat">
                            <p class="account-stat-label">Party code</p>
                            <p class="account-stat-value">Generated automatically</p>
                            <div class="small text-muted">Customer and supplier codes are assigned when saved.</div>
                        </div>
                    </div>
                </div>

                <div class="card account-side-card">
                    <div class="card-body">
                        <div class="form-check form-switch fs-5 mb-4">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">Active party</label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Create Party
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
        const openingDate = '{{ $openingDateDefault }}';
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

