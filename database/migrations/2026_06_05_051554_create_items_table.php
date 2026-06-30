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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('item_code')->unique();
            $table->string('name');
            $table->string('hsn_sac_code')->nullable();
            $table->enum('type', ['goods', 'service'])->default('goods');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('income_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('unit')->default('nos')->comment('nos, kg, ltr, mtr, etc.');
            $table->text('description')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('opening_stock', 15, 2)->default(0);
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->decimal('reorder_level', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_stockable')->default(true);
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();
            $table->string('deleted_by')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by_id')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
