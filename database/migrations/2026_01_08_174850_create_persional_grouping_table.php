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
        Schema::create('persional_grouping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('team_id');
            $table->string('group_name');
            $table->string('type')->default('Offense');
            $table->json('players'); 
            // Optional: add foreign keys if you have games, leagues, teams tables
            // $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            // $table->foreign('league_id')->references('id')->on('leagues')->onDelete('cascade');
            // $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persional_grouping');
    }
};
