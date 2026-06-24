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
                $table->unsignedBigInteger('league_id')->nullable()->after('team_id');
                $table->foreign('league_id')->references('id')->on('leagues')->onDelete('set null');
            }

            if (!Schema::hasColumn('team_groups', 'description')) {
                $table->text('description')->nullable()->after('group_name');
            }

            if (!Schema::hasColumn('team_groups', 'practice_players')) {
                $table->json('practice_players')->nullable()->after('players');
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
