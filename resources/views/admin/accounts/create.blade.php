@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
<div class="account-shell">
    <div class="account-hero p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="account-kicker mb-3"><i class="bi bi-diagram-3"></i> Account Master</span>
                <h1 class="h2 fw-bold mb-2">Create a new ledger account</h1>
                <p class="mb-0 account-hero-copy">Create clean chart-of-accounts entries with smart code generation, opening balance support, and asset-specific transaction mode control.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="#accountFormCard" class="btn btn-primary btn-lg me-2">
                    <i class="bi bi-arrow-down me-1"></i>Start Form
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
                        <p class="mb-0 account-note">Fields marked with * are required.</p>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#transactionModeHelpModal">
                        <i class="bi bi-info-circle me-1"></i>Transaction Mode Help
                    </button>
                </div>
                <div class="card-body">
                    <form id="accountForm" method="POST" action="{{ route('admin.accounts.store') }}" data-ajax="true" data-success-redirect="{{ route('admin.accounts.index') }}">
                        @csrf

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="account_name" class="form-label fw-semibold">Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="account_name" name="account_name" value="{{ old('account_name') }}" required placeholder="e.g., Cash, SBI Bank, Trade Payables">
                            </div>
                            <div class="col-md-4">
                                <label for="account_type" class="form-label fw-semibold">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg" id="account_type" name="account_type" required>
                                    <option value="">Select Type</option>
                                    <option value="asset" {{ old('account_type') === 'asset' || ($accountType ?? null) === 'asset' ? 'selected' : '' }}>Asset</option>
                                    <option value="liability" {{ old('account_type') === 'liability' || ($accountType ?? null) === 'liability' ? 'selected' : '' }}>Liability</option>
                                    <option value="income" {{ old('account_type') === 'income' || ($accountType ?? null) === 'income' ? 'selected' : '' }}>Income</option>
                                    <option value="expense" {{ old('account_type') === 'expense' || ($accountType ?? null) === 'expense' ? 'selected' : '' }}>Expense</option>
                                    <option value="equity" {{ old('account_type') === 'equity' || ($accountType ?? null) === 'equity' ? 'selected' : '' }}>Equity</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4 d-none" id="transaction_mode_row">
                            <div class="col-md-6">
                                <label for="transaction_mode" class="form-label fw-semibold">Transaction Mode</label>
                                <select class="form-select form-select-lg" id="transaction_mode" name="transaction_mode">
                                    <option value="">General Asset (not Cash/Bank)</option>
                                    <option value="cash" {{ old('transaction_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank" {{ old('transaction_mode') === 'bank' ? 'selected' : '' }}>Bank</option>
                                    <option value="od" {{ old('transaction_mode') === 'od' ? 'selected' : '' }}>OD</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="alert account-form-help mb-0 w-100">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    Select a mode only for Cash, Bank, or OD ledgers.
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3 align-items-end account-opening-fields">
                            <div class="col-md-4">
                                <label for="opening_balance" class="form-label fw-semibold mb-1">Opening Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0.00') }}" step="0.01" min="0">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="balance_type" class="form-label fw-semibold mb-1">Balance Type</label>
                                <select class="form-select" id="balance_type" name="balance_type">
                                    <option value="debit" {{ old('balance_type') === 'debit' ? 'selected' : '' }}>Debit</option>
                                    <option value="credit" {{ old('balance_type') === 'credit' ? 'selected' : '' }}>Credit</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="opening_date" class="form-label fw-semibold mb-1">Opening Date</label>
                                <input type="date" class="form-control" id="opening_date" name="opening_date" value="{{ old('opening_date', date('Y-m-d')) }}">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="remarks" class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="4" placeholder="Add usage hints or internal notes">{{ old('remarks') }}</textarea>
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">Active account</label>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-lg px-4">
                                    <i class="bi bi-check-circle me-2"></i>Create Account
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card account-side-card mb-4">
                <div class="card-body">
                    <div class="account-stat mb-3">
                        <p class="account-stat-label">Auto-generated code</p>
                        <p class="account-stat-value">Based on account type</p>
                        <div class="small text-muted">Code fills automatically when you select an account type.</div>
                    </div>
                    <div class="account-stat mb-3">
                        <p class="account-stat-label">Asset accounts</p>
                        <p class="account-stat-value">Cash / Bank / OD</p>
                        <div class="small text-muted">Use a transaction mode only for liquid asset ledgers.</div>
                    </div>
                    <div class="account-stat">
                        <p class="account-stat-label">Ledger impact</p>
                        <p class="account-stat-value">Opening balances flow to reports</p>
                        <div class="small text-muted">Saved accounts are immediately available in vouchers and ledger reports.</div>
                    </div>
                </div>
            </div>

            <div class="card account-side-card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Quick Tips</h6>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Use clear account names like Cash, HDFC Bank, Sales Revenue.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Select Asset only for real balances you track.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Keep opening balance and balance type accurate for reports.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="transactionModeHelpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0">
                <div>
                    <h5 class="modal-title mb-1">Transaction Mode Guide</h5>
                    <small class="text-white-50">Used only for Asset accounts</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <h6 class="fw-bold mb-2">Cash</h6>
                            <p class="mb-0 text-muted small">Use for physical cash balances or petty cash accounts.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <h6 class="fw-bold mb-2">Bank</h6>
                            <p class="mb-0 text-muted small">Use for current accounts, savings accounts, and online bank balances.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <h6 class="fw-bold mb-2">OD</h6>
                            <p class="mb-0 text-muted small">Use for overdraft / cash credit linked accounts.</p>
                        </div>
                    </div>
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

    function syncTransactionModeState() {
        const isAsset = $('#account_type').val() === 'asset';
        $('#transaction_mode_row').toggleClass('d-none', !isAsset);
        if (!isAsset) {
            $('#transaction_mode').val('');
        }
    }

    function syncBalanceType() {
        const accountType = $('#account_type').val();
        if (!accountType) {
            return;
        }
        $('#balance_type').val(defaultBalanceType(accountType));
    }

    $('#account_type').on('change', function() {
        syncTransactionModeState();
        syncBalanceType();
    });

    syncTransactionModeState();
    @if(!old('balance_type'))
    syncBalanceType();
    @endif
});
</script>
@endpush