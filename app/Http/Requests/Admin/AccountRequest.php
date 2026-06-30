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
        $accountId = $this->route('account');

        return [
            'account_code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('accounts', 'account_code')->ignore($accountId),
            ],
            'account_name' => 'required|string|max:255',
            'account_type' => ['required', Rule::in(['asset', 'liability', 'income', 'expense', 'equity'])],
            'opening_balance' => 'nullable|numeric|min:0',
            'balance_type' => ['nullable', Rule::in(['debit', 'credit'])],
            'opening_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
            'is_active' => 'boolean',
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
            'account_code.unique' => 'This account code is already taken',
        ];
    }
}
