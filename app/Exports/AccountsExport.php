<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AccountsExport implements FromCollection, WithHeadings, WithColumnFormatting
{
    protected Collection $accounts;

    public function __construct(Collection $accounts)
    {
        $this->accounts = $accounts;
    }

    public function collection()
    {
        return $this->accounts->map(function ($account) {
            return [
                $account->account_code,
                $account->account_name,
                ucfirst($account->account_type),
                $account->account_type === 'asset'
                    ? ($account->is_cash_bank_od ? 'Yes' : 'No')
                    : '-',
                $account->opening_balance,
                ucfirst($account->balance_type ?? 'debit'),
                $account->opening_date?->format('d-M-Y') ?? '',
                $account->is_active ? 'Active' : 'Inactive',
                $account->remarks ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Account Type',
            'Is Cash/Bank/OD',
            'Opening Balance',
            'Balance Type',
            'Opening Date',
            'Status',
            'Remarks',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}
