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
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->enum('calculation_type', ['addition', 'deduction'])->default('addition')->after('is_inclusive');
            $table->string('category')->nullable()->after('type');
            $table->text('notes')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->dropColumn(['calculation_type', 'category', 'notes']);
        });
    }
};