<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The first attempt created the table before failing on MySQL's long
        // auto-generated index name. Treat that existing schema as complete.
        if (Schema::hasTable('stock_value_entries')) {
            return;
        }

        Schema::create('stock_value_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete();
            $table->date('valuation_date');
            $table->decimal('stock_value', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip', 45)->nullable();
            $table->string('updated_by_ip', 45)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['company_id', 'financial_year_id', 'valuation_date'], 'unique_stock_value_entry_date');
            $table->index(
                ['company_id', 'financial_year_id', 'valuation_date'],
                'stock_value_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_value_entries');
    }
};
