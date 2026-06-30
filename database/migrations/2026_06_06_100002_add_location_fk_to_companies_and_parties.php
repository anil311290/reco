<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add FK columns to companies
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id')->nullable()->after('country');
            $table->unsignedBigInteger('state_id')->nullable()->after('state');
            $table->unsignedBigInteger('city_id')->nullable()->after('city');
        });

        // Add FK columns to parties
        Schema::table('parties', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id')->nullable()->after('country');
            $table->unsignedBigInteger('state_id')->nullable()->after('state');
            $table->unsignedBigInteger('city_id')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['country_id', 'state_id', 'city_id']);
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['country_id', 'state_id', 'city_id']);
        });
    }
};
