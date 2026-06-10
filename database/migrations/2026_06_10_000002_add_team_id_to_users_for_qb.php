<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->after('league_id');
            $table->foreign('team_id')->references('id')->on('league_teams')->nullOnDelete();
        });

        $qbUsers = DB::table('users')
            ->where('role', 'qb')
            ->whereNotNull('league_id')
            ->whereNull('team_id')
            ->orderBy('id')
            ->get();

        foreach ($qbUsers->groupBy('league_id') as $leagueId => $leagueQbs) {
            $teamIds = DB::table('league_teams')
                ->where('league_id', $leagueId)
                ->where(function ($query) {
                    $query->where('is_practice', 0)
                        ->orWhereNull('is_practice');
                })
                ->orderBy('id')
                ->pluck('id')
                ->all();

            if ($teamIds === []) {
                $teamIds = DB::table('league_teams')
                    ->where('league_id', $leagueId)
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();
            }

            foreach ($leagueQbs->values() as $index => $qb) {
                $teamId = $teamIds[$index] ?? ($teamIds[0] ?? null);

                if ($teamId !== null) {
                    DB::table('users')
                        ->where('id', $qb->id)
                        ->update(['team_id' => $teamId]);
                }
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['head_coach_id', 'league_id', 'team_id'], 'users_qb_league_team_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_qb_league_team_unique');
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
