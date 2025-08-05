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
        Schema::create('opponent_team_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('opponent_team_id');
            $table->string('name');
            $table->unsignedInteger('grouping_count')->default(1);
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('opponent_team_id')->references('id')->on('league_teams')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opponent_team_packages');
    }
};
