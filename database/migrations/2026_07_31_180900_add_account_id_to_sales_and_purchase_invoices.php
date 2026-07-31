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
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'account_id')) {
                $table->foreignId('account_id')
                    ->nullable()
                    ->after('party_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
                $table->index('account_id');
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'account_id')) {
                $table->foreignId('account_id')
                    ->nullable()
                    ->after('party_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
                $table->index('account_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'account_id')) {
                $table->dropIndex(['account_id']);
                $table->dropConstrainedForeignId('account_id');
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'account_id')) {
                $table->dropIndex(['account_id']);
                $table->dropConstrainedForeignId('account_id');
            }
        });
    }
};
