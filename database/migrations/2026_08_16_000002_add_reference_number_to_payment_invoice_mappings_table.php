<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoice_mappings', function (Blueprint $table) {
            $table->string('reference_number', 100)->nullable()->after('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoice_mappings', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
