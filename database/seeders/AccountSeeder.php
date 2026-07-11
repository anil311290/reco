<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) return;

        $fy = FinancialYear::where('company_id', $company->id)->first();
        $fyId = $fy?->id;

        $accounts = [
            ['account_code' => '1000', 'account_name' => 'Opening Balance Difference', 'account_type' => 'asset',     'opening_balance' => 0,      'is_system' => true,  'remarks' => 'Suspense account for opening balance differences.'],
            ['account_code' => '1250', 'account_name' => 'Accounts Receivable',        'account_type' => 'asset',     'opening_balance' => 0,      'is_system' => true],
            ['account_code' => '1500', 'account_name' => 'Accounts Payable',           'account_type' => 'liability', 'opening_balance' => 0,      'is_system' => true],
            ['account_code' => '1501', 'account_name' => 'Sales Revenue (AR)',         'account_type' => 'income',    'opening_balance' => 0,      'is_system' => true,  'remarks' => 'Default income account for AR transactions.'],
            ['account_code' => '1751', 'account_name' => 'Purchases (AP)',             'account_type' => 'expense',   'opening_balance' => 0,      'is_system' => true,  'remarks' => 'Default expense account for AP transactions.'],
            ['account_code' => '1001', 'account_name' => 'Cash in Hand',              'account_type' => 'asset',     'opening_balance' => 50000,  'transaction_mode' => 'cash', 'is_system' => true],
            ['account_code' => '1002', 'account_name' => 'Bank Account - SBI',        'account_type' => 'asset',     'opening_balance' => 250000, 'transaction_mode' => 'bank', 'is_system' => true],
        ];

        foreach ($accounts as $account) {
            $isSystem = (bool) ($account['is_system'] ?? false);

            $attributes = array_merge([
                'is_system'       => false,
                'entry_source'    => 'manual',
                'remarks'         => null,
                'balance_type'    => 'debit',
            ], $account, [
                'entry_source'    => $isSystem ? 'system' : 'manual',
                'company_id'       => $company->id,
                'financial_year_id' => $fyId,
                'is_active'        => true,
                'created_by'       => 1,
                'updated_by'       => 1,
                'created_by_ip'    => '127.0.0.1',
                'updated_by_ip'    => '127.0.0.1',
            ]);

            $accountModel = Account::withTrashed()->firstOrNew(
                [
                    'account_code' => $account['account_code'],
                ],
                $attributes
            );

            $accountModel->fill($attributes);

            if ($accountModel->trashed()) {
                $accountModel->restore();
            }

            $accountModel->save();
        }
    }
}
