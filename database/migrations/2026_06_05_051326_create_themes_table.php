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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->default('Default');
            $table->string('primary_color', 7)->default('#6366f1');
            $table->string('secondary_color', 7)->default('#8b5cf6');
            $table->string('accent_color', 7)->default('#06b6d4');
            $table->string('sidebar_color', 7)->default('#1e1b4b');
            $table->string('header_color', 7)->default('#ffffff');
            $table->string('text_color', 7)->default('#1f2937');
            $table->string('bg_color', 7)->default('#f9fafb');
            $table->string('font_family')->default('Inter');
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('login_bg_url')->nullable();
            $table->boolean('dark_mode')->default(false);
            $table->json('custom_css')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
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
        Schema::dropIfExists('themes');
    }
};
