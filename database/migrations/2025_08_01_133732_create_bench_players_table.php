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
        Schema::create('bench_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('player_id');
            $table->string('type')->nullable();
            $table->timestamps();

            // Foreign key constraints (optional but recommended)
            $table->foreign('team_id')->references('id')->on('league_teams')->onDelete('cascade');
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('cascade');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('team_players')->onDelete('cascade');

            // Optional: prevent duplicates
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bench_players');
    }
};
