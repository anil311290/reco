<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Concerns\ValidatesVoucherPayload;

class VoucherApiRequest extends BaseFormRequest
{
    use ValidatesVoucherPayload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->voucherRules();
    }

    public function messages(): array
    {
        return $this->voucherMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareVoucherPayload();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateVoucherPayload($validator);
        });
    }
}
