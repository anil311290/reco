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
        Schema::table('accounts', function (Blueprint $table) {
            // Drop unwanted columns if they exist
            if (Schema::hasColumn('accounts', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            
            if (Schema::hasColumn('accounts', 'is_bank_account')) {
                $table->dropColumn('is_bank_account');
            }
            
            if (Schema::hasColumn('accounts', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_bank_account')->default(false);
            $table->integer('sort_order')->default(0);
        });
    }
};
