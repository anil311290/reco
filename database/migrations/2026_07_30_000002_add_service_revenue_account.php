<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $companies = DB::table('companies')->pluck('id');

            foreach ($companies as $companyId) {
                $companyId = (int) $companyId;
                $existing = DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('account_code', '1502')
                    ->first();

                if ($existing && !(
                    (bool) $existing->is_system
                    && $existing->account_name === 'Service Revenue'
                    && $existing->account_type === 'income'
                )) {
                    DB::table('accounts')
                        ->where('id', $existing->id)
                        ->update([
                            'account_code' => $this->nextAvailableIncomeCode($companyId),
                            'updated_at' => now(),
                        ]);
                    $existing = null;
                }

                $financialYearId = DB::table('financial_years')
                    ->where('company_id', $companyId)
                    ->orderByDesc('is_current')
                    ->orderByDesc('id')
                    ->value('id');

                if ($existing) {
                    DB::table('accounts')
                        ->where('id', $existing->id)
                        ->update([
                            'financial_year_id' => $existing->financial_year_id ?: $financialYearId,
                            'account_name' => 'Service Revenue',
                            'account_type' => 'income',
                            'entry_source' => 'system',
                            'balance_type' => 'credit',
                            'opening_balance' => 0,
                            'remarks' => 'Default income ledger for service taxable totals on sales invoices.',
                            'is_active' => true,
                            'is_system' => true,
                            'deleted_at' => null,
                            'deleted_by' => null,
                            'deleted_by_id' => null,
                            'updated_at' => now(),
                        ]);
                    $serviceRevenueId = (int) $existing->id;
                } else {
                    $serviceRevenueId = (int) DB::table('accounts')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'account_code' => '1502',
                        'account_name' => 'Service Revenue',
                        'account_type' => 'income',
                        'entry_source' => 'system',
                        'transaction_mode' => null,
                        'opening_balance' => 0,
                        'balance_type' => 'credit',
                        'opening_date' => now()->toDateString(),
                        'remarks' => 'Default income ledger for service taxable totals on sales invoices.',
                        'is_active' => true,
                        'is_system' => true,
                        'version' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $salesRevenueId = DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('account_code', '1501')
                    ->value('id');

                DB::table('items')
                    ->where('company_id', $companyId)
                    ->where('type', 'service')
                    ->where(function ($query) use ($salesRevenueId) {
                        $query->whereNull('income_account_id');
                        if ($salesRevenueId) {
                            $query->orWhere('income_account_id', $salesRevenueId);
                        }
                    })
                    ->update([
                        'income_account_id' => $serviceRevenueId,
                        'updated_at' => now(),
                    ]);

                if ($salesRevenueId) {
                    DB::table('sales_invoice_lines')
                        ->where('line_type', 'service')
                        ->where('account_id', $salesRevenueId)
                        ->whereIn('sales_invoice_id', DB::table('sales_invoices')
                            ->where('company_id', $companyId)
                            ->select('id'))
                        ->update([
                            'account_id' => $serviceRevenueId,
                            'updated_at' => now(),
                        ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Accounting ledgers and historical references are intentionally retained.
    }

    private function nextAvailableIncomeCode(int $companyId): string
    {
        $usedCodes = DB::table('accounts')
            ->where('company_id', $companyId)
            ->pluck('account_code')
            ->map(fn ($code) => (string) $code)
            ->flip();

        for ($code = 1503; $code <= 1750; $code++) {
            if (!$usedCodes->has((string) $code)) {
                return (string) $code;
            }
        }

        throw new RuntimeException("Income account code range is exhausted for company {$companyId}.");
    }
};
