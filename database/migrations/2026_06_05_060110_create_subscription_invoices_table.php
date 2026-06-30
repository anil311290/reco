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
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled', 'refunded'])->default('draft');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->json('line_items')->nullable();
            $table->text('notes')->nullable();
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
