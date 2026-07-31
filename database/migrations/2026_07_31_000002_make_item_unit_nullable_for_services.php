<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('unit')->nullable()->default(null)->comment('nos, kg, ltr, mtr, etc. Goods only')->change();
        });

        DB::table('items')->where('type', 'service')->update(['unit' => null]);
    }

    public function down(): void
    {
        DB::table('items')->whereNull('unit')->update(['unit' => 'nos']);

        Schema::table('items', function (Blueprint $table) {
            $table->string('unit')->default('nos')->comment('nos, kg, ltr, mtr, etc.')->change();
        });
    }
};
