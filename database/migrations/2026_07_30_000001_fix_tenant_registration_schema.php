<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $roleIndexes = $this->indexNames('roles');

        if (in_array('roles_slug_unique', $roleIndexes, true)) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_slug_unique');
            });
        }

        if (!in_array('roles_company_slug_unique', $roleIndexes, true)) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['company_id', 'slug'], 'roles_company_slug_unique');
            });
        }

        $roleIndexes = $this->indexNames('roles');

        if (in_array('roles_company_id_slug_index', $roleIndexes, true)) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropIndex('roles_company_id_slug_index');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY status ENUM('pending', 'active', 'inactive', 'suspended') NOT NULL DEFAULT 'active'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('status', 'pending')->update(['status' => 'inactive']);
            DB::statement(
                "ALTER TABLE users MODIFY status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active'"
            );
        }

        $roleIndexes = $this->indexNames('roles');

        if (in_array('roles_company_slug_unique', $roleIndexes, true)) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_company_slug_unique');
            });
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('slug', 'roles_slug_unique');
            $table->index(['company_id', 'slug'], 'roles_company_id_slug_index');
        });
    }

    private function indexNames(string $table): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            return collect(DB::select("SHOW INDEX FROM {$table}"))
                ->pluck('Key_name')
                ->unique()
                ->values()
                ->all();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->pluck('name')
                ->values()
                ->all();
        }

        return Schema::getIndexListing($table);
    }
};
