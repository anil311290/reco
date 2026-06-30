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
        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable(); // web, android, ios
            $table->string('device_name')->nullable();
            $table->string('device_os')->nullable();
            $table->string('browser')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['success', 'failed', 'blocked'])->default('success');
            $table->string('failure_reason')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('logged_out_at')->nullable();
            $table->timestamp('created_at')->nullable();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'created_at']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_history');
    }
};
