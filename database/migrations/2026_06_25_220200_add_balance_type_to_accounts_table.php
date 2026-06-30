<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Add balance_type column if it doesn't exist
            if (!Schema::hasColumn('accounts', 'balance_type')) {
                $table->enum('balance_type', ['debit', 'credit'])->default('debit')->after('opening_balance');
            }
            
            // Remove parent_id if needed (kept in DB but not exposed in forms)
            // This migration just adds the balance_type, parent_id stays for data integrity
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'balance_type')) {
                $table->dropColumn('balance_type');
            }
        });
    }
};
