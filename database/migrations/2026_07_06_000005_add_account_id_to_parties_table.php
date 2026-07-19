<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            if (!Schema::hasColumn('parties', 'account_id')) {
                // Not unique: under the control-account model many parties share
                // one AR/AP control account.
                $table->foreignId('account_id')->nullable()->after('type')->constrained('accounts')->nullOnDelete();
            }
        });

        $parties = DB::table('parties')->whereNull('account_id')->orderBy('id')->get();

        foreach ($parties as $party) {
            $accountType = $party->type === 'creditor' ? 'liability' : 'asset';
            $balanceType = $party->type === 'creditor' ? 'credit' : 'debit';

            $accountCode = $this->nextAccountCode((int) $party->company_id, $accountType);

            $accountId = DB::table('accounts')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'company_id' => $party->company_id,
                'financial_year_id' => $party->financial_year_id,
                'account_code' => (string) $accountCode,
                'account_name' => trim(($party->name ?? 'Party') . ' [' . ($party->party_code ?? 'PRTY') . ']'),
                'account_type' => $accountType,
                'entry_source' => 'system',
                'transaction_mode' => null,
                'opening_balance' => (float) ($party->opening_balance ?? 0),
                'balance_type' => $balanceType,
                'opening_date' => $party->opening_date ?: now()->toDateString(),
                'remarks' => 'Auto-linked account for party ' . ($party->party_code ?? $party->id),
                'is_active' => (bool) ($party->is_active ?? true),
                'is_system' => false,
                'created_by' => $party->created_by,
                'updated_by' => $party->updated_by,
                'created_by_ip' => $party->created_by_ip,
                'updated_by_ip' => $party->updated_by_ip,
                'version' => 1,
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('parties')->where('id', $party->id)->update(['account_id' => $accountId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            if (Schema::hasColumn('parties', 'account_id')) {
                $table->dropConstrainedForeignId('account_id');
            }
        });
    }

    private function nextAccountCode(int $companyId, string $accountType): string
    {
        $ranges = [
            'asset' => ['start' => 1001, 'end' => 1249],
            'liability' => ['start' => 1251, 'end' => 1499],
            'income' => ['start' => 1502, 'end' => 1750],
            'expense' => ['start' => 1752, 'end' => 2000],
            'equity' => ['start' => 2001, 'end' => 2500],
        ];

        $reserved = ['1000', '1250', '1500', '1501', '1751'];

        $range = $ranges[$accountType] ?? ['start' => 2501, 'end' => 9999];

        $lastCode = DB::table('accounts')
            ->where('company_id', $companyId)
            ->whereRaw("CAST(account_code AS UNSIGNED) BETWEEN ? AND ?", [$range['start'], $range['end']])
            ->whereRaw("account_code REGEXP '^[0-9]+$'")
            ->orderByRaw('CAST(account_code AS UNSIGNED) DESC')
            ->value('account_code');

        $next = $lastCode ? ((int) $lastCode + 1) : $range['start'];

        while (in_array((string) $next, $reserved, true) && $next <= $range['end']) {
            $next++;
        }

        if ($next > $range['end']) {
            throw new RuntimeException("Account code range exhausted for party account creation in company {$companyId}");
        }

        return (string) $next;
    }
};
