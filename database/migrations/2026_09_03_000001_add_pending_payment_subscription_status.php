<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('trial', 'active', 'pending_payment', 'past_due', 'cancelled', 'expired', 'paused') NOT NULL DEFAULT 'trial'");
    }

    public function down(): void
    {
        DB::statement("UPDATE subscriptions SET status = 'past_due' WHERE status = 'pending_payment'");
        DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('trial', 'active', 'past_due', 'cancelled', 'expired', 'paused') NOT NULL DEFAULT 'trial'");
    }
};