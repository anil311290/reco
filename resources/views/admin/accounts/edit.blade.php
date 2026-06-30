@extends('layouts.app')

@section('title', 'Edit Account')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Edit Account: {{ $account->account_name }}</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.accounts.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Accounts
        </a>
    </div>
</div>

<div class="card card-form">
    <div class="card-body">
        <form id="accountForm" method="POST" action="{{ route('admin.accounts.update', $account->id) }}" data-ajax="true" data-success-redirect="{{ route('admin.accounts.index') }}">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="account_name" name="account_name" 
                           value="{{ old('account_name', $account->account_name) }}" required>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-12">
                    <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="account_type" name="account_type" required
                            {{ $account->is_system ? 'disabled' : '' }}>
                        <option value="">Select Type</option>
                        <option value="asset" {{ old('account_type', $account->account_type) === 'asset' ? 'selected' : '' }}>Asset</option>
                        <option value="liability" {{ old('account_type', $account->account_type) === 'liability' ? 'selected' : '' }}>Liability</option>
                        <option value="income" {{ old('account_type', $account->account_type) === 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ old('account_type', $account->account_type) === 'expense' ? 'selected' : '' }}>Expense</option>
                        <option value="equity" {{ old('account_type', $account->account_type) === 'equity' ? 'selected' : '' }}>Equity</option>
                    </select>
                    @if($account->is_system)
                        <input type="hidden" name="account_type" value="{{ $account->account_type }}">
                    @endif
                </div>

            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="opening_balance" class="form-label">Opening Balance</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control" id="opening_balance" name="opening_balance" 
                               value="{{ old('opening_balance', $account->opening_balance) }}" step="0.01" min="0">
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="balance_type" class="form-label">Balance Type</label>
                    <select class="form-select" id="balance_type" name="balance_type">
                        <option value="debit" {{ old('balance_type', $account->balance_type ?? 'debit') === 'debit' ? 'selected' : '' }}>Debit</option>
                        <option value="credit" {{ old('balance_type', $account->balance_type ?? 'debit') === 'credit' ? 'selected' : '' }}>Credit</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="opening_date" class="form-label">Opening Date</label>
                    <input type="date" class="form-control" id="opening_date" name="opening_date" 
                           value="{{ old('opening_date', $account->opening_date?->format('Y-m-d')) }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ old('remarks', $account->remarks) }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            @if($account->is_system)
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    This is a system account. Some fields cannot be modified.
                </div>
            @endif

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Update Account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

