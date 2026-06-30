<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Recode existing party codes from DEB####/CRD#### to AR###/AP###.
     */
    public function up(): void
    {
        DB::transaction(function () {

            // ─── Recode DEB#### → AR### ───────────────────────────────────────
            $debtors = DB::select("SELECT id, party_code FROM parties WHERE party_code LIKE 'DEB%'");

            foreach ($debtors as $i => $party) {
                $newCode = 'AR' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                DB::statement("UPDATE parties SET party_code = '{$newCode}' WHERE id = {$party->id}");
            }

            // ─── Recode CRD#### → AP### ───────────────────────────────────────
            $creditors = DB::select("SELECT id, party_code FROM parties WHERE party_code LIKE 'CRD%'");

            foreach ($creditors as $i => $party) {
                $newCode = 'AP' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                DB::statement("UPDATE parties SET party_code = '{$newCode}' WHERE id = {$party->id}");
            }
        });
    }

    public function down(): void
    {
        // Not reversible. Restore from backup if needed.
    }
};
