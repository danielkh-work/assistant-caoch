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
        Schema::create('practice_team_player_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('practice_team_player_id');
            $table->text('position_name');
            $table->text('meta')->nullable()->default(null); 
            $table->integer('sort')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_team_player_positions');
    }
};
