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
        Schema::create('razorpay_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('razorpay_order_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['created', 'attempted', 'paid', 'failed'])->default('created');
            $table->integer('attempts')->default(0);
            $table->json('notes')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('receipt')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index('razorpay_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razorpay_orders');
    }
};
