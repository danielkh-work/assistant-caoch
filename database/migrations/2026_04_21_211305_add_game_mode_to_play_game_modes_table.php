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
        Schema::table('play_game_modes', function (Blueprint $table) {
             $table->enum('game_mode', ['play', 'practice'])
             ->default('play')
              ->after('league_id'); // change position if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_game_modes', function (Blueprint $table) {
            $table->dropColumn('game_mode');
        });
    }
};
