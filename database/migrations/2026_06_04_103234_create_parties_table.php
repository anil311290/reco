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
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('party_code')->unique();
            $table->string('name');
            $table->enum('type', ['debtor', 'creditor']);
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->integer('credit_limit')->default(0);
            $table->integer('payment_terms_days')->default(30);
            $table->date('opening_date')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();
            $table->string('deleted_by')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();

            // Offline sync fields
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by_id')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
