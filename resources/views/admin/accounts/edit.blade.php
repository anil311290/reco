@extends('layouts.app')

@section('title', 'Edit Account')

@section('content')
<div class="account-shell">
    <div class="account-hero p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="account-kicker mb-3"><i class="bi bi-pencil-square"></i> Account Master</span>
                <h1 class="h2 fw-bold mb-2">Edit account details</h1>
                <p class="mb-0 account-hero-copy">Refine account metadata, opening balance, and asset cash-bank eligibility without disturbing ledger continuity.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="#accountFormCard" class="btn btn-primary btn-lg me-2">
                    <i class="bi bi-arrow-down me-1"></i>Go to Form
                </a>
                <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card account-section-card" id="accountFormCard">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">Account Details</h5>
                        <p class="mb-0 account-note">Editing: {{ $account->account_code }} - {{ $account->account_name }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <form id="accountForm" method="POST" action="{{ route('admin.accounts.update', $account->id) }}" data-ajax="true" data-success-redirect="{{ route('admin.accounts.index') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="account_name" class="form-label fw-semibold">Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="account_name" name="account_name" value="{{ old('account_name', $account->account_name) }}" required>
                                <div class="form-text">Renaming is safe; posted transactions remain linked by ledger ID.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="account_type" class="form-label fw-semibold">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg" id="account_type" name="account_type" required {{ ($account->is_system || $isInUse) ? 'disabled' : '' }}>
                                    <option value="">Select Type</option>
                                    <option value="asset" {{ old('account_type', $account->account_type) === 'asset' ? 'selected' : '' }}>Asset</option>
                                    <option value="liability" {{ old('account_type', $account->account_type) === 'liability' ? 'selected' : '' }}>Liability</option>
                                    <option value="income" {{ old('account_type', $account->account_type) === 'income' ? 'selected' : '' }}>Income</option>
                                    <option value="expense" {{ old('account_type', $account->account_type) === 'expense' ? 'selected' : '' }}>Expense</option>
                                    <option value="equity" {{ old('account_type', $account->account_type) === 'equity' ? 'selected' : '' }}>Equity</option>
                                </select>
                                @if($account->is_system || $isInUse)
                                    <input type="hidden" name="account_type" value="{{ $account->account_type }}">
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mb-4 {{ old('account_type', $account->account_type) === 'asset' ? '' : 'd-none' }}" id="cash_bank_toggle_row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <span class="form-label fw-semibold mb-0">Is Cash/Bank/OD?</span>
                                    <span class="form-text mt-0">
                                        <i class="bi bi-info-circle me-1"></i>Yes stores <strong>1</strong>, No stores <strong>0</strong>.
                                    </span>
                                </div>
                                <input type="hidden" name="is_cash_bank_od" value="0">
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" id="is_cash_bank_od" name="is_cash_bank_od" value="1" {{ old('is_cash_bank_od', $account->is_cash_bank_od ?? 0) ? 'checked' : '' }} {{ $isInUse ? 'disabled' : '' }}>
                                    <label class="form-check-label fw-semibold" for="is_cash_bank_od">Yes, this is a Cash/Bank/OD ledger</label>
                                </div>
                                @if($isInUse)
                                    <input type="hidden" name="is_cash_bank_od" value="{{ (int) $account->is_cash_bank_od }}">
                                @endif
                            </div>
                        </div>

                        <div class="row g-2 mb-3 align-items-end account-opening-fields">
                            <div class="col-md-4">
                                <label for="opening_balance" class="form-label fw-semibold mb-1">Opening Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="opening_balance" value="{{ $account->opening_balance }}" step="0.01" min="0" readonly>
                                </div>
                                <div class="form-text">Cannot be edited after create.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="balance_type" class="form-label fw-semibold mb-1">Balance Type</label>
                                <select class="form-select" id="balance_type" disabled>
                                    <option value="debit" {{ ($account->balance_type ?? 'debit') === 'debit' ? 'selected' : '' }}>Debit</option>
                                    <option value="credit" {{ ($account->balance_type ?? 'debit') === 'credit' ? 'selected' : '' }}>Credit</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="opening_date" class="form-label fw-semibold mb-1">Opening Date</label>
                                <input type="date" class="form-control" id="opening_date" value="{{ $account->opening_date?->format('Y-m-d') }}" readonly>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="remarks" class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="4" placeholder="Add usage hints or internal notes">{{ old('remarks', $account->remarks) }}</textarea>
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">Active account</label>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-lg px-4">
                                    <i class="bi bi-check-circle me-2"></i>Update Account
                                </button>
                            </div>
                        </div>

                        @if($account->is_system)
                            <div class="alert alert-info mt-4 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                This is a system account. Some fields cannot be modified.
                            </div>
                        @endif
                        @if($isInUse)
                            <div class="alert alert-warning mt-4 mb-0">
                                <i class="bi bi-shield-lock me-2"></i>
                                Transactions are posted to this ledger. You may rename it, update notes, or change its active status; classification and opening-balance fields are locked to preserve historical reports.
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card account-side-card mb-4">
                <div class="card-body">
                    <div class="account-stat mb-3">
                        <p class="account-stat-label">Account code</p>
                        <p class="account-stat-value">{{ $account->account_code }}</p>
                        <div class="small text-muted">Preserved from the existing record.</div>
                    </div>
                    <div class="account-stat mb-3">
                        <p class="account-stat-label">Account type</p>
                        <p class="account-stat-value">{{ ucfirst($account->account_type) }}</p>
                        <div class="small text-muted">Enable Is Cash/Bank/OD only when this ledger is used for payment/receipt cash-bank selection.</div>
                    </div>
                    <div class="account-stat">
                        <p class="account-stat-label">Current status</p>
                        <p class="account-stat-value">{{ $account->is_active ? 'Active' : 'Inactive' }}</p>
                        <div class="small text-muted">Status affects availability in vouchers and dropdowns.</div>
                    </div>
                </div>
            </div>

            <div class="card account-side-card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Quick Notes</h6>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Keep opening balance aligned with opening date.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Turn on Is Cash/Bank/OD for ledgers you want in Paid From / Received In dropdowns.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Turn it off for general asset ledgers.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function defaultBalanceType(accountType) {
        return (accountType === 'asset' || accountType === 'expense') ? 'debit' : 'credit';
    }

    function syncCashBankToggleState() {
        const isAsset = $('#account_type').val() === 'asset';

        $('#cash_bank_toggle_row').toggleClass('d-none', !isAsset);

        if (!isAsset) {
            $('#is_cash_bank_od').prop('checked', false);
        }
    }

    $('#account_type').on('change', function() {
        syncCashBankToggleState();
        if (!$('#balance_type').is(':disabled')) {
            $('#balance_type').val(defaultBalanceType($(this).val()));
        }
    });

    syncCashBankToggleState();
});
</script>
@endpush