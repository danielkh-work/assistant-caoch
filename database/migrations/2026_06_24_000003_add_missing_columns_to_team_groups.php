<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('team_groups', 'league_id')) {
                $table->unsignedBigInteger('league_id')->nullable();
                $table->foreign('league_id')->references('id')->on('leagues')->onDelete('set null');
            }

            if (!Schema::hasColumn('team_groups', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('team_groups', 'players')) {
                $table->json('players')->nullable();
            }

            if (!Schema::hasColumn('team_groups', 'practice_players')) {
                $table->json('practice_players')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('team_groups', function (Blueprint $table) {
            if (Schema::hasColumn('team_groups', 'league_id')) {
                $table->dropForeign(['league_id']);
                $table->dropColumn('league_id');
            }

            if (Schema::hasColumn('team_groups', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('team_groups', 'practice_players')) {
                $table->dropColumn('practice_players');
            }
        });
    }
};
