<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique('sales_invoices_invoice_number_unique');
            $table->unique(
                ['company_id', 'invoice_number'],
                'sales_invoices_company_invoice_number_unique'
            );
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropUnique('purchase_invoices_invoice_number_unique');
            $table->unique(
                ['company_id', 'invoice_number'],
                'purchase_invoices_company_invoice_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique('sales_invoices_company_invoice_number_unique');
            $table->unique('invoice_number', 'sales_invoices_invoice_number_unique');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropUnique('purchase_invoices_company_invoice_number_unique');
            $table->unique('invoice_number', 'purchase_invoices_invoice_number_unique');
        });
    }
};
