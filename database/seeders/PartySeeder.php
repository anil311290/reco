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
            // Debtors (AR)
            ['party_code' => 'AR001', 'name' => 'Reliance Industries Ltd',   'type' => 'debtor',   'mobile' => '+91 9876543001', 'email' => 'accounts@reliance.com',       'address' => 'Maker Chambers IV, Nariman Point, Mumbai',            'gstin' => '27AAACR5055K1Z4', 'opening_balance' => 125000],
            ['party_code' => 'AR002', 'name' => 'Tata Consultancy Services', 'type' => 'debtor',   'mobile' => '+91 9876543002', 'email' => 'billing@tcs.com',              'address' => 'TCS House, Raveline Street, Fort, Mumbai',             'gstin' => '27AAACT2727L1ZA', 'opening_balance' => 89000],
            ['party_code' => 'AR003', 'name' => 'Infosys Technologies',      'type' => 'debtor',   'mobile' => '+91 9876543003', 'email' => 'finance@infosys.com',          'address' => 'Electronics City, Hosur Road, Bangalore',             'gstin' => '29AAACI4357H1ZK', 'opening_balance' => 67000],
            ['party_code' => 'AR004', 'name' => 'Wipro Limited',             'type' => 'debtor',   'mobile' => '+91 9876543004', 'email' => 'ar@wipro.com',                 'address' => 'Doddakannelli, Sarjapur Road, Bangalore',             'gstin' => '29AAACW3059M1ZP', 'opening_balance' => 45000],
            ['party_code' => 'AR005', 'name' => 'HCL Technologies',         'type' => 'debtor',   'mobile' => '+91 9876543005', 'email' => 'collections@hcl.com',          'address' => 'A-10-11, Sector 3, Noida',                           'gstin' => '09AABCH8956R1Z3', 'opening_balance' => 34000],
            ['party_code' => 'AR006', 'name' => 'Mahindra & Mahindra',      'type' => 'debtor',   'mobile' => '+91 9876543006', 'email' => 'payments@mahindra.com',        'address' => 'Gateway Building, Apollo Bunder, Mumbai',            'gstin' => '27AABCM0939P1Z8', 'opening_balance' => 78000],
            ['party_code' => 'AR007', 'name' => 'Larsen & Toubro',          'type' => 'debtor',   'mobile' => '+91 9876543007', 'email' => 'finance@lnt.com',              'address' => 'L&T House, N.M. Marg, Ballard Estate, Mumbai',       'gstin' => '27AAACL3079R1Z5', 'opening_balance' => 156000],
            // Creditors (AP)
            ['party_code' => 'AP001', 'name' => 'Samsung India Electronics', 'type' => 'creditor', 'mobile' => '+91 9876543008', 'email' => 'vendor@samsung.com',          'address' => '2nd Floor, Tower C, Vipul Tech Square, Gurgaon',     'gstin' => '06AABCS8485M1ZL', 'opening_balance' => 95000],
            ['party_code' => 'AP002', 'name' => 'Apple India Pvt Ltd',      'type' => 'creditor', 'mobile' => '+91 9876543009', 'email' => 'ap@Apple.com',                 'address' => '19th Floor, One World Center, Mumbai',               'gstin' => '27AADCA3879E1Z7', 'opening_balance' => 210000],
            ['party_code' => 'AP003', 'name' => 'Dell Technologies India',   'type' => 'creditor', 'mobile' => '+91 9876543010', 'email' => 'india.vendor@dell.com',        'address' => 'Divyasree Omega, Whitefield, Bangalore',             'gstin' => '29AABCD6433N1ZQ', 'opening_balance' => 48000],
            ['party_code' => 'AP004', 'name' => 'HP India Sales Pvt Ltd',   'type' => 'creditor', 'mobile' => '+91 9876543011', 'email' => 'supply@hp.com',                'address' => '24, Salarpuria Arena, Hosur Road, Bangalore',        'gstin' => '29AABCH2583J1Z6', 'opening_balance' => 62000],
            ['party_code' => 'AP005', 'name' => 'Lenovo India Pvt Ltd',     'type' => 'creditor', 'mobile' => '+91 9876543012', 'email' => 'accounts@lenovo.com',          'address' => '4th Floor, Ferns Icon, Doddanekkundi, Bangalore',    'gstin' => '29AABCL3194P1Z5', 'opening_balance' => 38000],
            ['party_code' => 'AP006', 'name' => 'Amazon India Pvt Ltd',     'type' => 'creditor', 'mobile' => '+91 9876543013', 'email' => 'vendor-payments@amazon.in',    'address' => 'World Trade Centre, Brigade Gateway, Bangalore',     'gstin' => '29AABCA3120L1Z5', 'opening_balance' => 175000],
            ['party_code' => 'AP007', 'name' => 'Flipkart Internet Pvt Ltd', 'type' => 'creditor', 'mobile' => '+91 9876543014', 'email' => 'supplier@flipkart.com',       'address' => 'Vaishnavi Summit, Koramangala, Bangalore',           'gstin' => '29AABCF6239M1Z2', 'opening_balance' => 132000],
        ];

        foreach ($parties as $party) {
            Party::updateOrCreate(
                ['company_id' => $company->id, 'name' => $party['name']],
                array_merge($party, [
                    'company_id'    => $company->id,
                    'is_active'     => true,
                    'created_by'    => 1,
                    'updated_by'    => 1,
                    'created_by_ip' => '127.0.0.1',
                    'updated_by_ip' => '127.0.0.1',
                ])
            );
        }
    }
}
