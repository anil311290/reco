<?php

namespace App\Http\Requests\Api;

class TypedVoucherApiRequest extends VoucherApiRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->forcedVoucherType()) {
            $this->merge(['voucher_type' => $this->forcedVoucherType()]);
        }

        parent::prepareForValidation();
    }

    protected function forcedVoucherType(): ?string
    {
        return null;
    }
}
