<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;

class BankAccountRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'nullable|exists:accounts,id',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'account_holder_name' => 'nullable|string|max:255',
            'account_type' => ['required', \Illuminate\Validation\Rule::in(['savings', 'current', 'fixed_deposit', 'cc_od'])],
            'opening_balance' => 'nullable|numeric',
            'opening_date' => 'nullable|date',
            'upi_id' => 'nullable|string|max:100',
            'is_default' => 'boolean',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.required' => 'Bank name is required',
            'account_number.required' => 'Account number is required',
            'account_type.required' => 'Account type is required',
        ];
    }
}
