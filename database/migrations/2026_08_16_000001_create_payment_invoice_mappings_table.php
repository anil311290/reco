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
        Schema::create('payment_invoice_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            // Reference to payment/receipt voucher
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payment_voucher_id');

            // Flexible invoice reference (polymorphic pattern)
            $table->enum('invoice_type', ['sales', 'purchase']);
            $table->unsignedBigInteger('invoice_id');

            // Amounts
            $table->decimal('invoice_original_balance', 12, 2)->comment('Invoice balance at time of mapping');
            $table->decimal('amount_allocated', 12, 2)->comment('Amount supposed to settle');
            $table->decimal('amount_settled', 12, 2)->default(0)->comment('Amount actually settled');

            // Status tracking
            $table->enum('status', ['pending', 'partial', 'full', 'reversed'])->default('pending');
            $table->text('notes')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip', 45)->nullable();
            $table->string('updated_by_ip', 45)->nullable();
            $table->softDeletes();

            // Timestamps
            $table->timestamps();

            // Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('payment_voucher_id')->references('id')->on('vouchers')->onDelete('cascade');

            // Unique constraint: one mapping per payment-invoice combination
            $table->unique(['company_id', 'payment_voucher_id', 'invoice_type', 'invoice_id'], 'unique_payment_invoice_mapping');

            // Indexes for performance
            $table->index(['payment_voucher_id'], 'idx_payment_invoice_mappings_voucher');
            $table->index(['invoice_type', 'invoice_id'], 'idx_payment_invoice_mappings_invoice');
            $table->index(['company_id', 'invoice_type'], 'idx_payment_invoice_mappings_company_type');
            $table->index(['status'], 'idx_payment_invoice_mappings_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_invoice_mappings');
    }
};
