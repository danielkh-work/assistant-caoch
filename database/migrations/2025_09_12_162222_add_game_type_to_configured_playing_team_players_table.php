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
        Schema::table('configured_playing_team_players', function (Blueprint $table) {
            
             $table->tinyInteger('game_type')->default(1)->comment('1 = normal game, 2 = practice game')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configured_playing_team_players', function (Blueprint $table) {
            $table->dropColumn('game_type');
        });
    }
};
