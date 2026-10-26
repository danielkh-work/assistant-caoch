<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Removes the "hidden" league_play_overrides rows that were inserted by the
     * old (now removed) league-creation-time seeding step in SportController::store().
     * Starter visibility is now computed live from plays.is_starter_play, so these
     * leftover rows only serve to wrongly pin a league to whatever was non-starter
     * at its creation time, permanently, with no UI left to undo it.
     *
     * These rows are safely identifiable because the old seeding code deliberately
     * set created_by_user_id to null (a real head-coach hide always has a user id).
     */
    public function up(): void
    {
        DB::table('league_play_overrides')
            ->where('status', 'hidden')
            ->whereNull('created_by_user_id')
            ->delete();
    }

    public function down(): void
    {
        // Not reversible: the deleted rows carry no information that would let
        // us know which ones to recreate.
    }
};
