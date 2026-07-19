<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vouchers used both `narration` and `remarks` for the same purpose.
     * The UI now keeps only `narration` (Tally terminology), so the redundant
     * `remarks` column is removed.
     */
    public function up(): void
    {
        if (Schema::hasColumn('vouchers', 'remarks')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropColumn('remarks');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('vouchers', 'remarks')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->text('remarks')->nullable();
            });
        }
    }
};
