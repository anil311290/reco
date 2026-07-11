<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Party;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) return;

        $parties = [
            ['party_code' => 'AR001', 'name' => 'Default Customer', 'type' => 'debtor',   'mobile' => '+91 9000000001', 'email' => 'customer@example.com', 'address' => 'Default Customer Address', 'gstin' => '', 'opening_balance' => 0],
            ['party_code' => 'AP001', 'name' => 'Default Supplier', 'type' => 'creditor', 'mobile' => '+91 9000000002', 'email' => 'supplier@example.com', 'address' => 'Default Supplier Address', 'gstin' => '', 'opening_balance' => 0],
        ];

        foreach ($parties as $party) {
            Party::withTrashed()->updateOrCreate(
                ['party_code' => $party['party_code']],
                array_merge($party, [
                    'company_id' => $company->id,
                    'is_active' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_by_ip' => '127.0.0.1',
                    'updated_by_ip' => '127.0.0.1',
                ])
            );
        }
    }
}
