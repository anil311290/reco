<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Party legs post to shared AR/AP control accounts, so the party is tracked
     * per line via party_id (subsidiary detail) rather than by a per-party account.
     */
    public function up(): void
    {
        Schema::table('voucher_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('voucher_lines', 'party_id')) {
                $table->foreignId('party_id')->nullable()->after('account_id')
                    ->constrained('parties')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('voucher_lines', function (Blueprint $table) {
            if (Schema::hasColumn('voucher_lines', 'party_id')) {
                $table->dropConstrainedForeignId('party_id');
            }
        });
    }
};
