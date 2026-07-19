<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The journal_entries table was a write-only mirror of voucher lines that no
     * report, controller, or API ever read. All books are derived from the
     * `ledgers` table, so this redundant projection is removed.
     */
    public function up(): void
    {
        Schema::dropIfExists('journal_entries');
    }

    public function down(): void
    {
        // Intentionally irreversible: the journal_entries projection is obsolete.
    }
};
