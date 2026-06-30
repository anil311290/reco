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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->enum('status', ['trial', 'active', 'past_due', 'cancelled', 'expired', 'paused'])->default('trial');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime'])->default('monthly');
            $table->date('start_date');
            $table->date('trial_end_date')->nullable();
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->date('cancelled_at')->nullable();
            $table->date('pause_until')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('razorpay_subscription_id')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'status']);
            $table->index('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
