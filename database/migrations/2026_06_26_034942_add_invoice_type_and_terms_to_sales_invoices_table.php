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
            $table->string('invoice_type', 20)->default('item')->after('invoice_number'); // 'item' or 'service'
            $table->string('payment_terms', 100)->nullable()->after('notes');
            $table->string('delivery_terms', 100)->nullable()->after('payment_terms');
            $table->index(['company_id', 'invoice_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'invoice_type']);
            $table->dropColumn(['invoice_type', 'payment_terms', 'delivery_terms']);
        });
    }
};
