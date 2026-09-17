<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('recurrence_frequency', 20)->nullable()->after('is_recurring');
            $table->unsignedTinyInteger('recurrence_day_of_week')->nullable()->after('recurrence_frequency');
            $table->string('recurrence_monthly_type', 20)->nullable()->after('recurrence_day_of_week');
            $table->unsignedTinyInteger('recurrence_day_of_month')->nullable()->after('recurrence_monthly_type');
            $table->date('recurrence_next_run_at')->nullable()->after('recurrence_day_of_month');
            $table->date('recurrence_last_run_at')->nullable()->after('recurrence_next_run_at');
            $table->index(['is_recurring', 'recurrence_next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['is_recurring', 'recurrence_next_run_at']);
            $table->dropColumn([
                'recurrence_frequency',
                'recurrence_day_of_week',
                'recurrence_monthly_type',
                'recurrence_day_of_month',
                'recurrence_next_run_at',
                'recurrence_last_run_at',
            ]);
        });
    }
};