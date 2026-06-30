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
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin')->nullable()->after('password');
            $table->boolean('has_pin')->default(false)->after('pin');
            $table->boolean('app_lock_enabled')->default(false)->after('has_pin');
            $table->boolean('biometric_enabled')->default(false)->after('app_lock_enabled');
            $table->integer('auto_lock_timeout')->default(5)->after('biometric_enabled'); // minutes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pin', 'has_pin', 'app_lock_enabled', 
                'biometric_enabled', 'auto_lock_timeout'
            ]);
        });
    }
};
