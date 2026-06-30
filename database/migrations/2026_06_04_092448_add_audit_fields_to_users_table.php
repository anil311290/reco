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
            // Add deleted_by fields first (after soft deletes)
            $table->string('deleted_by')->nullable()->after('deleted_at');
            $table->unsignedBigInteger('deleted_by_id')->nullable()->after('deleted_by');

            // Add audit fields
            $table->unsignedBigInteger('created_by')->nullable()->after('deleted_by_id');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->string('created_by_ip')->nullable()->after('updated_by');
            $table->string('updated_by_ip')->nullable()->after('created_by_ip');

            // Offline sync fields
            $table->integer('version')->default(1)->after('updated_by_ip');
            $table->timestamp('synced_at')->nullable()->after('version');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by_id']);
            $table->dropColumn([
                'deleted_by', 'deleted_by_id',
                'created_by', 'updated_by',
                'created_by_ip', 'updated_by_ip'
            ]);
        });
    }
};
