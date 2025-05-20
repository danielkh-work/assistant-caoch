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
        Schema::create('play_game_modes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sport_id')->constrained('sports')->onDelete('cascade');
            $table->unsignedBigInteger('league_id')->constrained('leagues')->onDelete('cascade');
            $table->unsignedBigInteger('my_team_id')->constrained('league_teams')->onDelete('cascade');
            $table->unsignedBigInteger('oponent_team_id')->constrained('league_teams')->onDelete('cascade');
            $table->integer('my_team_score')->nullable();
            $table->integer('oponent_team_score')->nullable();
            $table->string('quater')->nullable();
            $table->string('downs')->nullable();
            $table->integer('status')->default(0)->comment('0=start 1= completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_game_modes');
    }
};
