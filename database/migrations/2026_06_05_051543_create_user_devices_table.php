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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_id')->comment('Unique device fingerprint');
            $table->string('device_type'); // web, android, ios
            $table->string('device_name')->nullable();
            $table->string('device_os')->nullable();
            $table->string('push_token')->nullable();
            $table->string('fcm_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['company_id', 'device_type']);
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
