<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Notification content
            $table->string('type'); // e.g. subscription.expiring, invoice.overdue, receivable.due
            $table->string('title');
            $table->text('message');
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('icon')->nullable(); // Bootstrap icon class
            $table->string('color')->nullable(); // Bootstrap color class

            // Deep link support
            $table->string('link_module')->nullable(); // e.g. sales-invoices, subscriptions
            $table->string('link_id')->nullable(); // record ID for deep link

            // State
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('channel')->default('in_app'); // in_app, email, sms, push
            $table->timestamp('sent_at')->nullable();

            // Metadata
            $table->json('data')->nullable(); // extra payload

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'user_id', 'is_read']);
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index('type');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
