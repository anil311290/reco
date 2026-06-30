<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // What to sync
            $table->string('table_name')->comment('Source table name');
            $table->string('record_uuid')->comment('UUID of the record to sync');
            $table->enum('operation', ['create', 'update', 'delete'])->default('create');

            // Payload
            $table->json('payload')->nullable()->comment('Full record data for create/update');
            $table->json('metadata')->nullable()->comment('Extra context for conflict resolution');

            // Status tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();

            // Device context
            $table->string('device_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            // Conflict resolution
            $table->integer('local_version')->nullable()->comment('Version from mobile');
            $table->integer('server_version')->nullable()->comment('Version on server');
            $table->string('conflict_resolution')->nullable(); // server_wins, client_wins, manual

            $table->timestamps();

            // Indexes
            $table->index(['table_name', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index('record_uuid');
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
