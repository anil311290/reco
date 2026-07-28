<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('payment_terms', 100)->nullable()->after('notes');
            $table->string('delivery_terms', 100)->nullable()->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_terms', 'delivery_terms']);
        });
    }
};
