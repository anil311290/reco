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
        Schema::create('ledger_party_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ledger_id')->constrained('ledgers')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'ledger_id']);
            $table->index(['ledger_id', 'party_id']);
            $table->index(['company_id', 'party_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_party_histories');
    }
};
