@extends('layouts.app')

@section('title', 'Edit Bank Account')

@section('content')
<div class="row mb-4">
    <div class="col-md-6"><h4 class="mb-0">Edit Bank Account</h4></div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.bank-accounts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="bankAccountForm">
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="bank_name" value="{{ $bankAccount->bank_name }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch Name</label>
                    <input type="text" class="form-control" name="branch_name" value="{{ $bankAccount->branch_name }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="account_number" value="{{ $bankAccount->account_number }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" class="form-control" name="ifsc_code" value="{{ $bankAccount->ifsc_code }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="account_type" required>
                        <option value="current" {{ $bankAccount->account_type == 'current' ? 'selected' : '' }}>Current</option>
                        <option value="savings" {{ $bankAccount->account_type == 'savings' ? 'selected' : '' }}>Savings</option>
                        <option value="fixed_deposit" {{ $bankAccount->account_type == 'fixed_deposit' ? 'selected' : '' }}>Fixed Deposit</option>
                        <option value="cc_od" {{ $bankAccount->account_type == 'cc_od' ? 'selected' : '' }}>CC / OD</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" class="form-control" name="account_holder_name" value="{{ $bankAccount->account_holder_name }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">UPI ID</label>
                    <input type="text" class="form-control" name="upi_id" value="{{ $bankAccount->upi_id }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Set as Default</label>
                    <select class="form-select" name="is_default">
                        <option value="0" {{ !$bankAccount->is_default ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $bankAccount->is_default ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" name="remarks" rows="2">{{ $bankAccount->remarks }}</textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Update</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
ajaxFormSubmit('#bankAccountForm', '{{ route("admin.bank-accounts.update", $bankAccount->id) }}', 'PUT', '{{ route("admin.bank-accounts.index") }}');
</script>
@endsection
