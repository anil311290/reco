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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('sales_invoice_id')->nullable();
            $table->unsignedBigInteger('purchase_invoice_id')->nullable();
            $table->string('voucher_number')->unique();
            $table->enum('voucher_type', ['income', 'expense', 'receipt', 'payment', 'journal', 'adjustment']);
            $table->date('voucher_date');
            $table->text('narration')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->text('remarks')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();
            $table->string('deleted_by')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();

            // Offline sync fields
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by_id')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'voucher_type']);
            $table->index(['company_id', 'voucher_date']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
