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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. GST 18%, GST 5%
            $table->string('code')->nullable(); // e.g. GST18
            $table->decimal('rate', 5, 2); // percentage
            $table->enum('type', ['gst', 'igst', 'cgst_sgst', 'vat', 'exempt'])->default('gst');
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
