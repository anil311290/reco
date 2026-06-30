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
            // ── System / Reserved ───────────────────────────────────────────
            ['account_code' => '1000', 'account_name' => 'Opening Balance Difference', 'account_type' => 'asset',     'opening_balance' => 0,      'is_system' => true,  'remarks' => 'Suspense account for opening balance differences.'],
            ['account_code' => '1250', 'account_name' => 'Accounts Receivable',        'account_type' => 'asset',     'opening_balance' => 0,      'is_system' => true],
            ['account_code' => '1500', 'account_name' => 'Accounts Payable',           'account_type' => 'liability', 'opening_balance' => 0,      'is_system' => true],
            ['account_code' => '1501', 'account_name' => 'Sales Revenue (AR)',         'account_type' => 'income',    'opening_balance' => 0,      'is_system' => true,  'remarks' => 'Default income account for AR transactions.'],
            ['account_code' => '1751', 'account_name' => 'Purchases (AP)',             'account_type' => 'expense',   'opening_balance' => 0,      'is_system' => true,  'remarks' => 'Default expense account for AP transactions.'],
            // ── Assets  1001–1249 ────────────────────────────────────────────
            ['account_code' => '1001', 'account_name' => 'Cash in Hand',              'account_type' => 'asset',     'opening_balance' => 50000,  'is_system' => true],
            ['account_code' => '1002', 'account_name' => 'Bank Account - SBI',        'account_type' => 'asset',     'opening_balance' => 250000, 'is_system' => true],
            ['account_code' => '1003', 'account_name' => 'Bank Account - HDFC',       'account_type' => 'asset',     'opening_balance' => 180000, 'is_system' => true],
            ['account_code' => '1004', 'account_name' => 'Inventory',                 'account_type' => 'asset',     'opening_balance' => 75000],
            ['account_code' => '1005', 'account_name' => 'Fixed Assets',              'account_type' => 'asset',     'opening_balance' => 500000],
            ['account_code' => '1006', 'account_name' => 'Office Equipment',          'account_type' => 'asset',     'opening_balance' => 120000],
            // ── Liabilities  1251–1499 ───────────────────────────────────────
            ['account_code' => '1251', 'account_name' => 'GST Payable',               'account_type' => 'liability', 'opening_balance' => 15000],
            ['account_code' => '1252', 'account_name' => 'TDS Payable',               'account_type' => 'liability', 'opening_balance' => 8000],
            ['account_code' => '1253', 'account_name' => 'Loan - SBI',                'account_type' => 'liability', 'opening_balance' => 300000],
            ['account_code' => '1254', 'account_name' => 'Credit Card Payable',       'account_type' => 'liability', 'opening_balance' => 25000],
            // ── Income  1502–1750 ────────────────────────────────────────────
            ['account_code' => '1502', 'account_name' => 'Sales Revenue',             'account_type' => 'income',    'opening_balance' => 0],
            ['account_code' => '1503', 'account_name' => 'Service Revenue',           'account_type' => 'income',    'opening_balance' => 0],
            ['account_code' => '1504', 'account_name' => 'Interest Income',           'account_type' => 'income',    'opening_balance' => 0],
            ['account_code' => '1505', 'account_name' => 'Commission Income',         'account_type' => 'income',    'opening_balance' => 0],
            ['account_code' => '1506', 'account_name' => 'Discount Received',         'account_type' => 'income',    'opening_balance' => 0],
            // ── Expenses  1752–2000 ──────────────────────────────────────────
            ['account_code' => '1752', 'account_name' => 'Purchases',                 'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1753', 'account_name' => 'Salary Expense',            'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1754', 'account_name' => 'Rent Expense',              'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1755', 'account_name' => 'Electricity Expense',       'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1756', 'account_name' => 'Office Supplies',           'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1757', 'account_name' => 'Travel Expense',            'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1758', 'account_name' => 'Marketing Expense',         'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1759', 'account_name' => 'Insurance Expense',         'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1760', 'account_name' => 'Telephone Expense',         'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1761', 'account_name' => 'Depreciation Expense',      'account_type' => 'expense',   'opening_balance' => 0],
            ['account_code' => '1762', 'account_name' => 'Discount Given',            'account_type' => 'expense',   'opening_balance' => 0],
            // ── Equity  2001–2500 ────────────────────────────────────────────
            ['account_code' => '2001', 'account_name' => 'Owner Equity',              'account_type' => 'equity',    'opening_balance' => 500000],
            ['account_code' => '2002', 'account_name' => 'Retained Earnings',         'account_type' => 'equity',    'opening_balance' => 200000],
            ['account_code' => '2003', 'account_name' => 'Capital Account',           'account_type' => 'equity',    'opening_balance' => 300000],
        ];

        foreach ($accounts as $account) {
            $attributes = array_merge([
                'is_system'       => false,
                'remarks'         => null,
                'balance_type'    => 'debit',
            ], $account, [
                'company_id'       => $company->id,
                'financial_year_id' => $fyId,
                'is_active'        => true,
                'created_by'       => 1,
                'updated_by'       => 1,
                'created_by_ip'    => '127.0.0.1',
                'updated_by_ip'    => '127.0.0.1',
            ]);

            $accountModel = Account::withTrashed()->firstOrNew(
                ['account_code' => $account['account_code']],
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
