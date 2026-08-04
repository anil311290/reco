<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Account code scheme:
    *   1000         → Opening Balance (suspense, system)
     *   1001–1249    → Assets  (user)
     *   1250         → Accounts Receivable (reserved, system)
     *   1251–1499    → Liabilities (user)
     *   1500         → Accounts Payable (reserved, system)
     *   1501         → Sales Revenue / AR Income (reserved, system)
     *   1502–1750    → Income (user)
     *   1751         → Purchases / AP Expense (reserved, system)
     *   1752–2000    → Expenses (user)
     *   2001–2500    → Equity (user)
     */
    public function up(): void
    {
        DB::transaction(function () {

            $companyId = DB::table('companies')->value('id');
            $fyId      = $companyId ? DB::table('financial_years')->where('company_id', $companyId)->value('id') : null;

            if (!$companyId || !$fyId) {
                return;
            }

            $driver = DB::getDriverName();

            // ─── Step 1: Recode existing seeded accounts ──────────────────────
            $remap = [
                // Assets
                '1000' => '1001',   // Cash in Hand
                '1001' => '1002',   // Bank Account - SBI
                '1002' => '1003',   // Bank Account - HDFC
                '1020' => '1004',   // Inventory
                '1030' => '1005',   // Fixed Assets
                '1031' => '1006',   // Office Equipment
                // Accounts Receivable → reserved 1250
                '1010' => '1250',
                // Liabilities
                '2001' => '1251',   // GST Payable
                '2002' => '1252',   // TDS Payable
                '2010' => '1253',   // Loan - SBI
                '2020' => '1254',   // Credit Card Payable
                // Accounts Payable → reserved 1500
                '2000' => '1500',
                // Income  (1501 reserved — inserted fresh below)
                '3000' => '1502',   // Sales Revenue
                '3001' => '1503',   // Service Revenue
                '3002' => '1504',   // Interest Income
                '3003' => '1505',   // Commission Income
                '3010' => '1506',   // Discount Received
                // Expenses (1751 reserved — inserted fresh below)
                '4000' => '1752',   // Purchases
                '4001' => '1753',   // Salary Expense
                '4002' => '1754',   // Rent Expense
                '4003' => '1755',   // Electricity Expense
                '4004' => '1756',   // Office Supplies
                '4005' => '1757',   // Travel Expense
                '4006' => '1758',   // Marketing Expense
                '4007' => '1759',   // Insurance Expense
                '4008' => '1760',   // Telephone Expense
                '4009' => '1761',   // Depreciation Expense
                '4010' => '1762',   // Discount Given
                // Equity
                '5000' => '2001',   // Owner Equity
                '5001' => '2002',   // Retained Earnings
                '5002' => '2003',   // Capital Account
            ];

            // Fetch IDs by exact string match to avoid MySQL numeric cast errors
            // Pass via two-step: temp prefix → final code (avoids unique conflicts)
            foreach ($remap as $old => $new) {
                DB::statement("UPDATE accounts SET account_code = 'TMP_{$old}' WHERE account_code = '{$old}'");
            }
            foreach ($remap as $old => $new) {
                DB::statement("UPDATE accounts SET account_code = '{$new}' WHERE account_code = 'TMP_{$old}'");
            }

            // ─── Step 2: Recode user-created prefix-style accounts ────────────
            // Assign them a code in the proper range by finding the next available slot.
            if ($driver === 'mysql') {
                $prefixAccounts = DB::select("SELECT id, account_code, account_type FROM accounts WHERE account_code REGEXP '^[A-Z]'");
            } else {
                $prefixAccounts = DB::select("SELECT id, account_code, account_type FROM accounts WHERE account_code GLOB '[A-Z]*'");
            }

            foreach ($prefixAccounts as $acc) {
                $range = match ($acc->account_type) {
                    'asset'     => ['start' => 1001, 'end' => 1249],
                    'liability' => ['start' => 1251, 'end' => 1499],
                    'income'    => ['start' => 1502, 'end' => 1750],
                    'expense'   => ['start' => 1752, 'end' => 2000],
                    'equity'    => ['start' => 2001, 'end' => 2500],
                    default     => ['start' => 2501, 'end' => 9999],
                };

                $reserved = ['1000', '1250', '1500', '1501', '1751'];

                if ($driver === 'mysql') {
                    $used = DB::selectOne(
                        "SELECT account_code FROM accounts WHERE account_code REGEXP '^[0-9]+$' AND CAST(account_code AS UNSIGNED) BETWEEN ? AND ? ORDER BY CAST(account_code AS UNSIGNED) DESC LIMIT 1",
                        [$range['start'], $range['end']]
                    );
                } else {
                    $used = DB::selectOne(
                        "SELECT account_code FROM accounts WHERE account_code GLOB '[0-9]*' AND CAST(account_code AS UNSIGNED) BETWEEN ? AND ? ORDER BY CAST(account_code AS UNSIGNED) DESC LIMIT 1",
                        [$range['start'], $range['end']]
                    );
                }

                $next = $used ? ((int) $used->account_code + 1) : $range['start'];
                while (in_array((string) $next, $reserved) && $next <= $range['end']) {
                    $next++;
                }

                if ($next <= $range['end']) {
                    DB::statement("UPDATE accounts SET account_code = ? WHERE id = ?", [(string) $next, $acc->id]);
                }
            }

            // ─── Step 3: Mark AR and AP as system accounts ───────────────────
            DB::table('accounts')
                ->whereIn('account_code', ['1250', '1500'])
                ->update(['is_system' => true]);

            // ─── Step 4: Insert reserved system accounts if missing ───────────
            $base = [
                'company_id'      => $companyId,
                'financial_year_id' => $fyId,
                'opening_balance' => 0,
                'balance_type'    => 'debit',
                'opening_date'    => now()->startOfYear(),
                'is_active'       => true,
                'is_system'       => true,
                'created_by'      => 1,
                'updated_by'      => 1,
                'created_by_ip'   => '127.0.0.1',
                'updated_by_ip'   => '127.0.0.1',
                'version'         => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            $systemAccounts = [
                [
                    'account_code' => '1000',
                    'account_name' => 'Opening Balance',
                    'account_type' => 'asset',
                    'remarks'      => 'System suspense account. Appears in Balance Sheet only when opening balances do not balance.',
                ],
                [
                    'account_code' => '1501',
                    'account_name' => 'Sales Revenue (AR)',
                    'account_type' => 'income',
                    'balance_type' => 'credit',
                    'remarks'      => 'Reserved: default income account for Accounts Receivable transactions.',
                ],
                [
                    'account_code' => '1751',
                    'account_name' => 'Purchases (AP)',
                    'account_type' => 'expense',
                    'remarks'      => 'Reserved: default expense account for Accounts Payable transactions.',
                ],
            ];

            foreach ($systemAccounts as $sys) {
                if (!DB::table('accounts')->where('account_code', $sys['account_code'])->exists()) {
                    $uuid = \Illuminate\Support\Str::uuid()->toString();
                    DB::table('accounts')->insert(array_merge($base, $sys, ['uuid' => $uuid]));
                }
            }
        });
    }

    public function down(): void
    {
        // Reversal omitted — data migration of this nature is not safely reversible.
        // Restore from a backup if needed.
    }
};
