<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales_invoices', 'invoice_type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $indexExists = DB::selectOne(
                "SELECT COUNT(1) AS cnt
                 FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                   AND table_name = 'sales_invoices'
                   AND index_name = 'sales_invoices_company_id_invoice_type_index'"
            );

            if ($indexExists && (int) $indexExists->cnt > 0) {
                Schema::table('sales_invoices', function (Blueprint $table) {
                    $table->dropIndex('sales_invoices_company_id_invoice_type_index');
                });
            }
        } else {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropIndex(['company_id', 'invoice_type']);
            });
        }

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_invoices', 'invoice_type')) {
            return;
        }

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('invoice_type', 20)->default('item')->after('invoice_number');
            $table->index(['company_id', 'invoice_type']);
        });
    }
};
