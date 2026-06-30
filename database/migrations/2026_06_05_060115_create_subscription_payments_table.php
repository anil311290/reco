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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('subscription_invoices')->nullOnDelete();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index('razorpay_payment_id');
            $table->index('razorpay_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
