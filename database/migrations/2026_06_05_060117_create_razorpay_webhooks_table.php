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
        Schema::create('razorpay_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('razorpay_event_id')->unique();
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'failed', 'ignored'])->default('received');
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('event_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razorpay_webhooks');
    }
};
