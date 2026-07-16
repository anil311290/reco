<?php

namespace App\Http\Requests\Api;

class AdjustmentVoucherApiRequest extends TypedVoucherApiRequest
{
    protected function forcedVoucherType(): ?string
    {
        return 'journal';
    }
}
