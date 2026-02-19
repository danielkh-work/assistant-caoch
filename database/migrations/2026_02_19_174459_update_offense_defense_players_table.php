<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::table('offense_defense_players', function (Blueprint $table) {
            $table->unsignedBigInteger('player_id')->nullable()->change();
            $table->unsignedBigInteger('practice_player_id')->nullable()->after('player_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offense_defense_players', function (Blueprint $table) {
            $table->dropColumn('practice_player_id');
            $table->unsignedBigInteger('player_id')->nullable(false)->change();
        });
    }
};
