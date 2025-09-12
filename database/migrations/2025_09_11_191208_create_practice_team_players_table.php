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
        Schema::create('practice_team_players', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->nullable();
        $table->unsignedBigInteger('player_id')->nullable();
         $table->unsignedBigInteger('league_id')->nullable();

      
        $table->string('name')->nullable();
        $table->string('number')->nullable();
        $table->string('position')->nullable()->default('1');
        $table->double('size');
        $table->double('speed');
        
        $table->integer('strength');
        $table->string('type')->nullable();
        $table->double('weight', 8, 2)->nullable();
        $table->double('height', 8, 2)->nullable();
        $table->date('dob')->nullable();
        $table->string('image')->nullable();
        $table->string('position_value')->nullable();
        $table->string('ofp')->nullable();
        $table->timestamps();

   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_team_players');
    }
};
