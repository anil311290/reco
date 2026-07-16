<?php

namespace App\Http\Requests\Api;

class PaymentVoucherApiRequest extends TypedVoucherApiRequest
{
    protected function forcedVoucherType(): ?string
    {
        return 'payment';
    }
}
