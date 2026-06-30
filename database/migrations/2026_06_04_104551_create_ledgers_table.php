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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('reference_type')->nullable(); // voucher, opening, adjustment, sales_invoice, purchase_invoice
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('running_balance', 15, 2)->default(0);
            $table->enum('balance_type', ['debit', 'credit'])->default('debit');

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();

            // Offline sync fields
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'account_id', 'transaction_date']);
            $table->index(['company_id', 'financial_year_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
