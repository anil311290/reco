<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'entry_source')) {
                $table->enum('entry_source', ['manual', 'system'])
                    ->default('manual')
                    ->after('account_type');
            }
        });

        DB::table('accounts')
            ->where('is_system', true)
            ->update(['entry_source' => 'system']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'entry_source')) {
                $table->dropColumn('entry_source');
            }
        });
    }
};
