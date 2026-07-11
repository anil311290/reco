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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entry_date');
            $table->enum('module', ['sales', 'purchase', 'voucher', 'adjustment', 'receipt', 'payment'])->default('voucher');
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ledger_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('amount_signed', 15, 2)->default(0);
            $table->string('head_name_snapshot')->nullable();
            $table->text('narration')->nullable();
            $table->unsignedInteger('line_no')->default(1);
            $table->enum('status', ['posted', 'cancelled'])->default('posted');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();
            $table->string('deleted_by')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['company_id', 'module', 'source_type', 'source_id', 'line_no'], 'journal_entries_source_line_unique');
            $table->index(['company_id', 'financial_year_id', 'entry_date']);
            $table->index(['company_id', 'account_id', 'entry_date']);
            $table->index(['company_id', 'party_id', 'entry_date']);
            $table->index(['company_id', 'head_name_snapshot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
