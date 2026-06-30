@extends('layouts.app')

@section('title', 'Add Bank Account')

@section('content')
<div class="row mb-4">
    <div class="col-md-6"><h4 class="mb-0">Add Bank Account</h4></div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.bank-accounts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="bankAccountForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="bank_name" required placeholder="e.g. State Bank of India">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch Name</label>
                    <input type="text" class="form-control" name="branch_name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="account_number" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" class="form-control" name="ifsc_code" placeholder="e.g. SBIN0001234">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="account_type" required>
                        <option value="current">Current Account</option>
                        <option value="savings">Savings Account</option>
                        <option value="fixed_deposit">Fixed Deposit</option>
                        <option value="cc_od">CC / OD</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" class="form-control" name="account_holder_name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">UPI ID</label>
                    <input type="text" class="form-control" name="upi_id" placeholder="name@upi">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Opening Balance</label>
                    <input type="number" class="form-control" name="opening_balance" step="0.01" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Opening Date</label>
                    <input type="date" class="form-control" name="opening_date">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Linked Account</label>
                    <select class="form-select" name="account_id">
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Set as Default</label>
                    <select class="form-select" name="is_default">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" name="remarks" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
ajaxFormSubmit('#bankAccountForm', '{{ route("admin.bank-accounts.store") }}', 'POST', '{{ route("admin.bank-accounts.index") }}');
</script>
@endsection
