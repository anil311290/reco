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
            $table->uuid('uuid')->unique()->after('id');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->enum('role', ['admin', 'manager', 'accountant', 'viewer'])->default('viewer')->after('avatar');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('role');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropUnique('users_uuid_unique');
            $table->dropColumn([
                'uuid', 'company_id', 'phone', 'avatar', 'role', 'status',
                'last_login_at', 'last_login_ip', 'deleted_at'
            ]);
        });
    }
};
