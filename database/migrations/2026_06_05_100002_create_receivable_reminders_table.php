<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivable_reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            // Reminder scheduling
            $table->date('due_date');
            $table->date('reminder_date')->comment('When to send the reminder');
            $table->integer('reminder_sequence')->default(1)->comment('1st, 2nd, 3rd reminder');

            // Reminder details
            $table->string('channel')->default('whatsapp'); // whatsapp, sms, email
            $table->string('template_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email_address')->nullable();
            $table->text('message_content')->nullable();

            // Status tracking
            $table->enum('status', ['pending', 'scheduled', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Amount tracking
            $table->decimal('invoice_total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->integer('days_overdue')->default(0);

            // Type
            $table->string('type')->default('automatic'); // automatic, manual

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['party_id', 'status']);
            $table->index(['sales_invoice_id', 'status']);
            $table->index(['reminder_date', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_reminders');
    }
};
