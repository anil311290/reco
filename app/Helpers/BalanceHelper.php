<?php

namespace App\Helpers;

class BalanceHelper
{
    /**
     * Short accounting suffix for a balance type.
     */
    public static function drCr(?string $type, string $empty = '-'): string
    {
        return match (strtolower(trim((string) $type))) {
            'debit' => 'Dr',
            'credit' => 'Cr',
            default => $empty,
        };
    }
}
