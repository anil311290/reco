<?php

namespace App\Exports;

use App\Helpers\DateHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PartyLedgerExport implements FromArray, WithHeadings, WithTitle
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return array_map(function (array $row) {
            $entry = $row['entry'];
            $particulars = $entry->description
                ?: ($entry->voucher->narration ?? '—');

            return [
                DateHelper::formatDate($entry->transaction_date),
                $entry->voucher?->voucher_number ?: '—',
                $particulars,
                $entry->debit > 0 ? number_format((float) $entry->debit, 2, '.', '') : '',
                $entry->credit > 0 ? number_format((float) $entry->credit, 2, '.', '') : '',
                number_format($row['running_balance'], 2, '.', '') . ' ' . strtoupper($row['running_type']),
            ];
        }, $this->rows);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Voucher #',
            'Particulars',
            'Debit',
            'Credit',
            'Balance',
        ];
    }

    public function title(): string
    {
        return 'Party Ledger';
    }
}
