<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Raw ENUM MODIFY is MySQL-only; SQLite (tests) stores status as text.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('trial', 'active', 'pending_payment', 'past_due', 'cancelled', 'expired', 'paused') NOT NULL DEFAULT 'trial'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE subscriptions SET status = 'past_due' WHERE status = 'pending_payment'");
            DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('trial', 'active', 'past_due', 'cancelled', 'expired', 'paused') NOT NULL DEFAULT 'trial'");
        }
    }
};