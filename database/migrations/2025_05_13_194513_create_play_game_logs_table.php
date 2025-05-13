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
        Schema::create('play_game_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id')->nullable();
            $table->unsignedBigInteger('sport_id')->nullable()->constrained('sports')->onDelete('cascade');
            $table->unsignedBigInteger('league_id')->nullable()->constrained('leagues')->onDelete('cascade');
            $table->unsignedBigInteger('player_id')->nullable();
            $table->unsignedBigInteger('my_team_id')->nullable()->constrained('league_teams')->onDelete('cascade');;
            $table->unsignedBigInteger('oponent_team_id')->nullable()->constrained('league_teams')->onDelete('cascade');;
            $table->string('quater')->nullable();
            $table->string('downs')->nullable();
            $table->string('current_position')->nullable();
            $table->string('target')->nullable()->comment('offense defense');
            $table->string('my_points')->nullable();
            $table->string('oponent_points')->nullable();
            $table->string('time')->nullable();
            $table->string('type_of_log')->nullable()->comment('point, quater , target , donws ,current_state');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_game_logs');
    }
};
