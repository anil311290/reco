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
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('content')->nullable();
            $table->string('template')->default('default');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('show_in_nav')->default(false);
            $table->integer('nav_order')->default(0);
            $table->integer('version')->default(1);
            $table->timestamp('synced_at')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_by_ip')->nullable();
            $table->string('updated_by_ip')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index('status');
            $table->index(['show_in_nav', 'nav_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_pages');
    }
};
