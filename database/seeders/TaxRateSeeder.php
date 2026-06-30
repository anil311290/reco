<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) return;

        $taxRates = [
            [
                'tax_name' => 'Exempt',
                'tax_code' => 'EXEMPT',
                'tax_rate' => 0,
                'tax_type' => 'addition',
                'tax_category' => 'OTHER',
                'notes' => 'No tax applied.',
                'status' => 'active',
            ],
            [
                'tax_name' => 'GST 0%',
                'tax_code' => 'GST0',
                'tax_rate' => 0,
                'tax_type' => 'addition',
                'tax_category' => 'GST',
                'notes' => 'Zero-rated GST for configured transactions.',
                'status' => 'active',
            ],
            [
                'tax_name' => 'GST 5%',
                'tax_code' => 'GST5',
                'tax_rate' => 5,
                'tax_type' => 'addition',
                'tax_category' => 'GST',
                'notes' => 'Standard GST slab at 5%.',
                'status' => 'active',
            ],
            [
                'tax_name' => 'GST 12%',
                'tax_code' => 'GST12',
                'tax_rate' => 12,
                'tax_type' => 'addition',
                'tax_category' => 'GST',
                'notes' => 'Standard GST slab at 12%.',
                'status' => 'active',
            ],
            [
                'tax_name' => 'GST 18%',
                'tax_code' => 'GST18',
                'tax_rate' => 18,
                'tax_type' => 'addition',
                'tax_category' => 'GST',
                'notes' => 'Standard GST slab at 18%.',
                'status' => 'active',
            ],
            [
                'tax_name' => 'GST 28%',
                'tax_code' => 'GST28',
                'tax_rate' => 28,
                'tax_type' => 'addition',
                'tax_category' => 'GST',
                'notes' => 'Standard GST slab at 28%.',
                'status' => 'active',
            ],
            [
                'tax_name' => 'Input Tax Credit',
                'tax_code' => 'ITC',
                'tax_rate' => 0,
                'tax_type' => 'deduction',
                'tax_category' => 'GST',
                'notes' => 'Deduction entry used for input tax credit adjustments.',
                'status' => 'active',
            ],
        ];

        foreach ($taxRates as $taxRate) {
            TaxRate::firstOrCreate(
                [
                    'tax_code' => $taxRate['tax_code'],
                    'company_id' => $company->id,
                ],
                array_merge($taxRate, [
                    'company_id' => $company->id,
                ])
            );
        }
    }
}
