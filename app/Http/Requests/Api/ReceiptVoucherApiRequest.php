<?php

namespace App\Http\Requests\Api;

class ReceiptVoucherApiRequest extends TypedVoucherApiRequest
{
    protected function forcedVoucherType(): ?string
    {
        return 'receipt';
    }
}
