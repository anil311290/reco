<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->nullable();
            $table->string('phone_code', 10)->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['country_id', 'is_active']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['state_id', 'is_active']);
            $table->index(['country_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};
