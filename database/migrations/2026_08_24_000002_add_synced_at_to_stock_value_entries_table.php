<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_value_entries') || Schema::hasColumn('stock_value_entries', 'synced_at')) {
            return;
        }

        Schema::table('stock_value_entries', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable()->after('version');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('stock_value_entries') || !Schema::hasColumn('stock_value_entries', 'synced_at')) {
            return;
        }

        Schema::table('stock_value_entries', function (Blueprint $table) {
            $table->dropColumn('synced_at');
        });
    }
};
