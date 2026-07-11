<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            return;
        }

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
                'tax_name' => 'GST 18%',
                'tax_code' => 'GST18',
                'tax_rate' => 18,
                'tax_type' => 'addition',
                'tax_category' => 'GST',
                'notes' => 'Standard GST slab at 18%.',
                'status' => 'active',
            ],
        ];

        $columns = Schema::getColumnListing('tax_rates');

        foreach ($taxRates as $taxRate) {
            $payload = [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'tax_code' => $taxRate['tax_code'],
                'tax_name' => $taxRate['tax_name'],
                'tax_rate' => $taxRate['tax_rate'],
                'tax_type' => $taxRate['tax_type'],
                'tax_category' => $taxRate['tax_category'],
                'notes' => $taxRate['notes'],
                'status' => $taxRate['status'],
                // Legacy columns still present on partial / sqlite migrate paths
                'name' => $taxRate['tax_name'],
                'code' => $taxRate['tax_code'],
                'rate' => $taxRate['tax_rate'],
                'type' => 'gst',
                'category' => $taxRate['tax_category'],
                'calculation_type' => $taxRate['tax_type'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $payload = array_filter(
                $payload,
                fn ($value, $key) => in_array($key, $columns, true),
                ARRAY_FILTER_USE_BOTH
            );

            $exists = TaxRate::query()
                ->when(in_array('tax_code', $columns, true), fn ($q) => $q->where('tax_code', $taxRate['tax_code']))
                ->when(in_array('code', $columns, true) && !in_array('tax_code', $columns, true), fn ($q) => $q->where('code', $taxRate['tax_code']))
                ->where('company_id', $company->id)
                ->exists();

            if (!$exists) {
                TaxRate::query()->insert($payload);
            }
        }
    }
}
