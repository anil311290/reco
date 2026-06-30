<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Recode all old-format party codes to AR### / AP###.
     * Handles: DEB-001, DEB0001, CRE-001, CRD0001 and any other legacy formats.
     */
    public function up(): void
    {
        DB::transaction(function () {

            // ── Debtors (AR) ─────────────────────────────────────────────────
            $debtors = DB::select(
                "SELECT id FROM parties WHERE type = 'debtor' AND party_code NOT LIKE 'AR%' ORDER BY id"
            );

            foreach ($debtors as $i => $party) {
                $newCode = 'AR' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                DB::statement("UPDATE parties SET party_code = '{$newCode}' WHERE id = {$party->id}");
            }

            // ── Creditors (AP) ────────────────────────────────────────────────
            $creditors = DB::select(
                "SELECT id FROM parties WHERE type = 'creditor' AND party_code NOT LIKE 'AP%' ORDER BY id"
            );

            foreach ($creditors as $i => $party) {
                $newCode = 'AP' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                DB::statement("UPDATE parties SET party_code = '{$newCode}' WHERE id = {$party->id}");
            }
        });
    }

    public function down(): void
    {
        // Not reversible.
    }
};
