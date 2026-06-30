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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices')->nullOnDelete();
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['sales_invoice_id']);
            $table->dropForeign(['purchase_invoice_id']);
        });
    }
};
