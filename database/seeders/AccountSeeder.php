<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Setting;
use App\Services\LedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            return;
        }

        $fy = FinancialYear::where('company_id', $company->id)->first();
        $fyId = $fy?->id;

        /*
         | Default system ledgers (fixed account codes):
         | 1000 Opening Balance Difference — balancing suspense
         | 1100 Purchase Tax — tax on purchase invoice lines
         | 1250 Accounts Receivable (AR) — debtors control
         | 1251 Sales Tax — tax on sales invoice lines
         | 1500 Accounts Payable (AP) — creditors control
         | 1501 Sales Revenue — goods taxable totals
         | 1502 Service Revenue — service taxable totals
         | 1751 Purchase Expenses — purchase invoice item totals
         */
        $accounts = [
            [
                'account_code' => Account::CODE_SUSPENSE,
                'account_name' => 'Opening Balance',
                'account_type' => 'asset',
                'balance_type' => 'debit',
                'opening_balance' => 0,
                'is_system' => true,
                'remarks' => 'System suspense ledger used for balancing opening entries.',
            ],
            [
                'account_code' => Account::CODE_PURCHASE_TAX,
                'account_name' => 'Purchase Tax',
                'account_type' => 'asset',
                'balance_type' => 'debit',
                'opening_balance' => 0,
                'is_system' => true,
                'remarks' => 'Default ledger for tax amount from purchase invoice lines.',
            ],
            [
                'account_code' => Account::CODE_AR,
                'account_name' => 'Accounts Receivable',
                'account_type' => 'asset',
                'balance_type' => 'debit',
                'opening_balance' => 0,
                'is_system' => true,
                'remarks' => 'Control ledger for all debtor (customer) balances.',
            ],
            [
                'account_code' => Account::CODE_SALES_TAX,
                'account_name' => 'Sales Tax',
                'account_type' => 'liability',
                'balance_type' => 'credit',
                'opening_balance' => 0,
                'is_system' => true,
                'remarks' => 'Default ledger for tax amount from sales invoice lines.',
            ],
            [
                'account_code' => Account::CODE_AP,
                'account_name' => 'Accounts Payable',
                'account_type' => 'liability',
                'balance_type' => 'credit',
                'opening_balance' => 0,
                'is_system' => true,
                'remarks' => 'Control ledger for all creditor (supplier) balances.',
            ],
            [
                'account_code' => Account::CODE_AR_INCOME,
                'account_name' => 'Sales Revenue',
                'account_type' => 'income',
                'balance_type' => 'credit',
                'opening_balance' => 0,
                'is_system' => true,
                'remarks' => 'Default income ledger for goods taxable totals on sales invoices.',
            ],
            [
                'account_code' => Account::CODE_SERVICE_INCOME,
                'account_name' => 'Service Revenue',
                'account_type' => 'income',
                'balance_type' => 'credit',
                'opening_balance' => 0,
                'is_system' => true,
                'remarks' => 'Default income ledger for service taxable totals on sales invoices.',
            ],
            [
                'account_code' => Account::CODE_AP_EXPENSE,
                'account_name' => 'Purchase Expenses',
                'account_type' => 'expense',
                'balance_type' => 'debit',
                'opening_balance' => 0,
                'is_system' => true,
                'remarks' => 'Default expense ledger for item totals on purchase invoices.',
            ],
        ];

        $createdByCode = [];

        foreach ($accounts as $account) {
            $isSystem = (bool) ($account['is_system'] ?? false);

            $attributes = array_merge([
                'is_system' => false,
                'entry_source' => 'manual',
                'remarks' => null,
                'balance_type' => 'debit',
                'transaction_mode' => null,
            ], $account, [
                'uuid' => (string) Str::uuid(),
                'entry_source' => $isSystem ? 'system' : 'manual',
                'company_id' => $company->id,
                'financial_year_id' => $fyId,
                'is_active' => true,
                'opening_date' => now()->toDateString(),
                'created_by' => 1,
                'updated_by' => 1,
                'created_by_ip' => '127.0.0.1',
                'updated_by_ip' => '127.0.0.1',
            ]);

            $accountModel = Account::withTrashed()->firstOrNew(
                [
                    'company_id' => $company->id,
                    'account_code' => $account['account_code'],
                ],
                $attributes
            );

            unset($attributes['uuid']);
            if (!$accountModel->exists) {
                $accountModel->uuid = (string) Str::uuid();
            }

            $accountModel->fill($attributes);

            if ($accountModel->trashed()) {
                $accountModel->restore();
            }

            $accountModel->save();
            $createdByCode[$account['account_code']] = $accountModel;

            if (round((float) $accountModel->opening_balance, 2) > 0) {
                app(LedgerService::class)->createAccountOpeningBalanceEntries($accountModel);
            }
        }

        if (isset($createdByCode[Account::CODE_SALES_TAX])) {
            Setting::setValue(
                'sales_tax_ledger_id',
                (string) $createdByCode[Account::CODE_SALES_TAX]->id,
                $company->id,
                'accounting'
            );
        }

        if (isset($createdByCode[Account::CODE_PURCHASE_TAX])) {
            Setting::setValue(
                'purchase_tax_ledger_id',
                (string) $createdByCode[Account::CODE_PURCHASE_TAX]->id,
                $company->id,
                'accounting'
            );
        }
    }
}
