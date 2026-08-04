@extends('layouts.app')

@section('title', 'Create Account')

@php
    $openingDateDefault = auth()->user()->company->currentFinancialYear?->start_date?->format('Y-m-d') ?? date('Y-m-d');
@endphp

@section('content')
<div class="account-shell">
    <div class="account-hero p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="account-kicker mb-3"><i class="bi bi-diagram-3"></i> Account Master</span>
                <h1 class="h2 fw-bold mb-2">Create a new ledger account</h1>
                <p class="mb-0 account-hero-copy">Create clean chart-of-accounts entries with smart code generation, opening balance support, and asset cash-bank eligibility control.</p>
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
                <div class="card-header">
                    <div>
                        <h5 class="mb-1">Account Details</h5>
                        <p class="mb-0 account-note">Fields marked with * are required.</p>
                    </div>
                </div>
                <div class="card-body">
                    <form id="accountForm" method="POST" action="{{ route('admin.accounts.store') }}">
                        @csrf
                        <input type="hidden" id="duplicate_action" name="duplicate_action" value="">

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="account_name" class="form-label fw-semibold">Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="account_name" name="account_name" value="{{ old('account_name') }}" required placeholder="e.g., Cash, SBI Bank, Trade Payables">
                            </div>
                            <div class="col-md-4">
                                <label for="account_type" class="form-label fw-semibold">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="account_type" name="account_type" required>
                                    <option value="">Select Type</option>
                                    <option value="asset" {{ old('account_type') === 'asset' || ($accountType ?? null) === 'asset' ? 'selected' : '' }}>Asset</option>
                                    <option value="liability" {{ old('account_type') === 'liability' || ($accountType ?? null) === 'liability' ? 'selected' : '' }}>Liability</option>
                                    <option value="income" {{ old('account_type') === 'income' || ($accountType ?? null) === 'income' ? 'selected' : '' }}>Income</option>
                                    <option value="expense" {{ old('account_type') === 'expense' || ($accountType ?? null) === 'expense' ? 'selected' : '' }}>Expense</option>
                                    <option value="equity" {{ old('account_type') === 'equity' || ($accountType ?? null) === 'equity' ? 'selected' : '' }}>Equity</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4 d-none" id="cash_bank_toggle_row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <span class="form-label fw-semibold mb-0">Is Cash/Bank/OD?</span>
                                    <span class="form-text mt-0">
                                        <i class="bi bi-info-circle me-1"></i>Yes stores <strong>1</strong>, No stores <strong>0</strong>.
                                    </span>
                                </div>
                                <input type="hidden" name="is_cash_bank_od" value="0">
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" id="is_cash_bank_od" name="is_cash_bank_od" value="1" {{ old('is_cash_bank_od', 0) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="is_cash_bank_od">Yes, this is a Cash/Bank/OD ledger</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3 align-items-end account-opening-fields">
                            <div class="col-md-6">
                                <label for="opening_balance" class="form-label fw-semibold mb-1">Opening Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0.00') }}" step="0.01" min="0">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="balance_type" class="form-label fw-semibold mb-1">Balance Type</label>
                                <select class="form-select" id="balance_type" name="balance_type">
                                    <option value="debit" {{ old('balance_type') === 'debit' ? 'selected' : '' }}>Debit</option>
                                    <option value="credit" {{ old('balance_type') === 'credit' ? 'selected' : '' }}>Credit</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-text mb-4">
                            <i class="bi bi-calendar-event me-1"></i>Opening date is auto-set to the current financial year start date: <strong>{{ $openingDateDefault }}</strong>.
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
                        <div class="small text-muted">Enable only when this asset should be available in Receipt/Payment selection.</div>
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
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Opening date is auto-set to current financial year start date.</li>
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

    function syncBalanceType() {
        const accountType = $('#account_type').val();
        if (!accountType) {
            return;
        }
        $('#balance_type').val(defaultBalanceType(accountType));
    }

    $('#account_type').on('change', function() {
        $('#duplicate_action').val('');
        syncCashBankToggleState();
        syncBalanceType();
    });

    $('#account_name').on('input', function() {
        $('#duplicate_action').val('');
    });

    $('#accountForm').on('submit.openingBalanceConfirmation', function(event) {
        const form = $(this);
        if (form.data('opening-balance-confirmed')) {
            form.removeData('opening-balance-confirmed');
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const amount = parseFloat($('#opening_balance').val()) || 0;
        const balanceType = $('#balance_type option:selected').text();
        const openingDate = '{{ $openingDateDefault }}';
        const formattedAmount = amount.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        Swal.fire({
            title: 'Confirm Opening Balance',
            html: amount > 0
                ? `Opening balance of <strong>₹${formattedAmount}</strong> (${balanceType}) dated <strong>${openingDate}</strong> will be posted and <strong>cannot be edited later</strong>.`
                : `No opening balance will be posted. Opening balance <strong>cannot be set later</strong> after the ledger is created.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, create ledger',
            cancelButtonText: 'Review again'
        }).then(function(result) {
            if (result.isConfirmed) {
                form.data('opening-balance-confirmed', true);
                form.trigger('submit');
            }
        });
    });

    ajaxFormSubmit(
        '#accountForm',
        '{{ route("admin.accounts.store") }}',
        'POST',
        '{{ route("admin.accounts.index") }}',
        function(xhr) {
            const response = xhr.responseJSON;
            if (xhr.status !== 409 || response?.code !== 'SOFT_DELETED_ACCOUNT_EXISTS') {
                return false;
            }

            const deletedAccount = response.data;
            Swal.fire({
                title: 'Deleted Account Found',
                text: `${deletedAccount.account_name} (${deletedAccount.account_code}) already exists in deleted records. Restore it or create a new ledger entry?`,
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#16a34a',
                denyButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Restore Account',
                denyButtonText: 'Create New Entry',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (!result.isConfirmed && !result.isDenied) {
                    return;
                }

                $('#duplicate_action').val(result.isConfirmed ? 'restore' : 'new_entry');
                $('#accountForm').data('opening-balance-confirmed', true).trigger('submit');
            });

            syncCashBankToggleState();

            return true;
        }
    );

    syncCashBankToggleState();
    @if(!old('balance_type'))
    syncBalanceType();
    @endif
});
</script>
@endpush