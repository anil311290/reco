<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropUnique('parties_party_code_unique');
            $table->unique(
                ['company_id', 'party_code'],
                'parties_company_party_code_unique'
            );
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropUnique('vouchers_voucher_number_unique');
            $table->unique(
                ['company_id', 'voucher_number'],
                'vouchers_company_voucher_number_unique'
            );
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique('items_item_code_unique');
            $table->unique(
                ['company_id', 'item_code'],
                'items_company_item_code_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropUnique('parties_company_party_code_unique');
            $table->unique('party_code', 'parties_party_code_unique');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropUnique('vouchers_company_voucher_number_unique');
            $table->unique('voucher_number', 'vouchers_voucher_number_unique');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique('items_company_item_code_unique');
            $table->unique('item_code', 'items_item_code_unique');
        });
    }
};
