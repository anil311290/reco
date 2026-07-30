<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $accountId = $this->route('account') ?? $this->route('id');
        return [
            'account_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'account_name')
                    ->ignore($accountId)
                    ->withoutTrashed()
                    ->where(function ($query) {
                        $companyId = $this->user()?->company_id;

                        return $query
                            ->where('company_id', $companyId)
                            ->where('account_type', $this->input('account_type'));
                    }),
            ],
            'account_type' => ['required', Rule::in(['asset', 'liability', 'income', 'expense', 'equity'])],
            'transaction_mode' => [
                Rule::in(['cash', 'bank', 'od']),
                'nullable',
            ],
            'opening_balance' => 'nullable|numeric|min:0',
            'balance_type' => ['nullable', Rule::in(['debit', 'credit'])],
            'opening_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'duplicate_action' => ['nullable', Rule::in(['restore', 'new_entry'])],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'account_name.required' => 'Account name is required',
            'account_type.required' => 'Account type is required',
            'account_type.in' => 'Invalid account type',
            'account_name.unique' => 'An account with this name already exists for this type',
            'transaction_mode.in' => 'Transaction mode must be Cash, Bank, or OD',
        ];
    }
}
