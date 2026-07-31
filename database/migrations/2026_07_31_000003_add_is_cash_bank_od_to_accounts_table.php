<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'is_cash_bank_od')) {
                $table->boolean('is_cash_bank_od')
                    ->default(false)
                    ->after('entry_source');
            }
        });

        if (Schema::hasColumn('accounts', 'transaction_mode')) {
            DB::table('accounts')
                ->whereIn('transaction_mode', ['cash', 'bank', 'od'])
                ->update(['is_cash_bank_od' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'is_cash_bank_od')) {
                $table->dropColumn('is_cash_bank_od');
            }
        });
    }
};
