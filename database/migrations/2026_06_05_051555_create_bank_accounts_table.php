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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('account_number');
            $table->string('ifsc_code')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->enum('account_type', ['savings', 'current', 'fixed_deposit', 'cc_od'])->default('current');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_date')->nullable();
            $table->string('upi_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();
            $table->string('deleted_by')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by_id')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
