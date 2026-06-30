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
        Schema::create('pricing_displays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('badge')->nullable();
            $table->string('highlight_color', 7)->nullable();
            $table->text('description_short')->nullable();
            $table->text('description_long')->nullable();
            $table->json('features_list')->nullable();
            $table->integer('sort_order')->default(0);
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
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_displays');
    }
};
