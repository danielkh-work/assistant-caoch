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
        Schema::table('play_game_logs', function (Blueprint $table) {
              $table->longText('practice_players')
                
                  ->nullable()
                  ->after('players');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_game_logs', function (Blueprint $table) {
             $table->dropColumn('practice_players');
        });
    }
};
