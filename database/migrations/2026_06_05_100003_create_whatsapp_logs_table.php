<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained()->nullOnDelete();

            // Message details
            $table->string('phone_number');
            $table->string('template_name')->nullable();
            $table->text('message_content')->nullable();
            $table->string('message_type')->default('text'); // text, template, media, document

            // Status tracking
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])->default('queued');
            $table->string('external_message_id')->nullable(); // WhatsApp message ID
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('failure_reason')->nullable();

            // Response metadata
            $table->json('request_payload')->nullable();
            $table->json('response_metadata')->nullable();
            $table->integer('retry_count')->default(0);

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['party_id', 'status']);
            $table->index('phone_number');
            $table->index('external_message_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
