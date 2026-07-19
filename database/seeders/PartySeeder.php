<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\State;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            return;
        }

        $financialYearId = FinancialYear::where('company_id', $company->id)
            ->where('is_current', true)
            ->value('id')
            ?? FinancialYear::where('company_id', $company->id)->value('id');

        $india = Country::where('iso2', 'IN')->first();
        $state = $india
            ? State::where('country_id', $india->id)->where('code', 'MH')->first()
            : null;
        $city = $state
            ? City::where('state_id', $state->id)->where('name', 'Mumbai')->first()
            : null;

        $arAccountId = Account::where('company_id', $company->id)
            ->where('account_code', Account::CODE_AR)
            ->value('id');
        $apAccountId = Account::where('company_id', $company->id)
            ->where('account_code', Account::CODE_AP)
            ->value('id');

        $common = [
            'company_id' => $company->id,
            'financial_year_id' => $financialYearId,
            'address' => '1st Floor, Business Tower',
            'country' => $india?->name ?? 'India',
            'state' => $state?->name,
            'city' => $city?->name,
            'country_id' => $india?->id,
            'state_id' => $state?->id,
            'city_id' => $city?->id,
            'postal_code' => '400001',
            'opening_balance' => 0,
            'opening_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
            'created_by_ip' => '127.0.0.1',
            'updated_by_ip' => '127.0.0.1',
        ];

        $parties = [
            array_merge($common, [
                'party_code' => 'AR001',
                'name' => 'Default Customer',
                'type' => 'debtor',
                'account_id' => $arAccountId,
                'opening_balance_type' => 'debit',
                'mobile' => '+91 9000000001',
                'email' => 'customer@example.com',
                'gstin' => '27AAAAA0000A1Z5',
            ]),
            array_merge($common, [
                'party_code' => 'AP001',
                'name' => 'Default Supplier',
                'type' => 'creditor',
                'account_id' => $apAccountId,
                'opening_balance_type' => 'credit',
                'mobile' => '+91 9000000002',
                'email' => 'supplier@example.com',
                'gstin' => '27BBBBB0000B1Z5',
            ]),
        ];

        foreach ($parties as $party) {
            Party::withTrashed()->updateOrCreate(
                ['party_code' => $party['party_code']],
                $party
            );
        }
    }
}
