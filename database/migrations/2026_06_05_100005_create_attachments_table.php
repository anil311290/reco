<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            // Polymorphic relationship
            $table->string('module_type')->comment('e.g. sales_invoices, purchase_invoices, companies');
            $table->unsignedBigInteger('module_id')->comment('ID of the related record');

            // File details
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_disk')->default('local'); // local, s3, etc.
            $table->unsignedBigInteger('file_size')->default(0)->comment('Size in bytes');
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();

            // Categorization
            $table->string('category')->nullable(); // invoice_pdf, gst_certificate, pan_card, business_registration, etc.
            $table->string('description')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['module_type', 'module_id']);
            $table->index(['company_id', 'module_type']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
