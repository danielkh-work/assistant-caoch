<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $count = DB::table('plays')->update([
                'is_global' => true,
                'league_id' => null,
            ]);
            \Illuminate\Support\Facades\Log::info("Global Plays backfill: converted {$count} existing plays to Global.");
        });
    }

    public function down(): void
    {
        // Intentionally irreversible in a generic way - which league_id each row
        // originally had is gone once this runs. If this needs undoing, restore
        // from the pre-migration backup instead of trusting an automatic down().
        throw new \RuntimeException('This backfill is not reversible - restore from backup if needed.');
    }
};
