<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherApiRequest extends FormRequest
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
        return [
            'voucher_type' => ['required', Rule::in(['income', 'expense', 'receipt', 'payment', 'journal', 'adjustment'])],
            'voucher_date' => 'required|date',
            'party_id' => 'nullable|exists:parties,id',
            'narration' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:500',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'voucher_type.required' => 'Voucher type is required',
            'voucher_date.required' => 'Voucher date is required',
            'lines.required' => 'At least one voucher line is required',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure lines have proper structure
        if ($this->has('lines')) {
            $lines = collect($this->lines)->map(function ($line) {
                return [
                    'account_id' => $line['account_id'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ];
            })->toArray();

            $this->merge(['lines' => $lines]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('lines') && is_array($this->lines)) {
                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($this->lines as $line) {
                    $totalDebit += $line['debit'] ?? 0;
                    $totalCredit += $line['credit'] ?? 0;
                }

                if (abs($totalDebit - $totalCredit) > 0.01) {
                    $validator->errors()->add(
                        'lines',
                        'Voucher must be balanced. Total debit must equal total credit.'
                    );
                }
            }
        });
    }
}
