<?php

namespace App\Exports;

use App\Helpers\DateHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ItemHistoryExport implements FromArray, WithHeadings, WithTitle
{
    protected array $rows;
    protected string $itemName;

    public function __construct(array $rows, string $itemName)
    {
        $this->rows = $rows;
        $this->itemName = $itemName;
    }

    public function array(): array
    {
        return array_map(function (array $row) {
            return [
                $row['date'] ? DateHelper::formatDate($row['date']) : '—',
                $row['type_label'],
                $row['invoice_number'] ?: '—',
                $row['party_name'] ?: '—',
                $row['qty_in'] > 0 ? number_format($row['qty_in'], 3, '.', '') : '',
                $row['qty_out'] > 0 ? number_format($row['qty_out'], 3, '.', '') : '',
                $row['rate'] > 0 ? number_format($row['rate'], 2, '.', '') : '',
                $row['amount'] > 0 ? number_format($row['amount'], 2, '.', '') : '',
                number_format($row['running_qty'], 3, '.', ''),
            ];
        }, $this->rows);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Invoice #',
            'Party',
            'Qty In',
            'Qty Out',
            'Rate',
            'Amount',
            'Balance Qty',
        ];
    }

    public function title(): string
    {
        return 'Item History';
    }
}
