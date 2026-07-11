<?php

namespace App\Models\Concerns;

use App\Helpers\DateHelper;
use DateTimeInterface;

trait FormatsHumanReadableDates
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return DateHelper::serialize($date);
    }
}
