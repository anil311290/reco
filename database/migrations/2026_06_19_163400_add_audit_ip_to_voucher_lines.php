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
        Schema::table('voucher_lines', function (Blueprint $table) {
            // Add audit IP fields after updated_by
            $table->string('created_by_ip')->nullable()->after('created_by');
            $table->string('updated_by_ip')->nullable()->after('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_lines', function (Blueprint $table) {
            $table->dropColumn('created_by_ip');
            $table->dropColumn('updated_by_ip');
        });
    }
};
