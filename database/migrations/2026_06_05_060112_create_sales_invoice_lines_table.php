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
        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 15, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('sales_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
    }
};
