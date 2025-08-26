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
        Schema::create('websocket_scoreboards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('game_id')->nullable();
            $table->integer('left_score')->default(0);
            $table->integer('right_score')->default(0);
            $table->string('action')->nullable();
            $table->boolean('is_start')->default(false);
            $table->string('time')->nullable();
            $table->string('quarter')->nullable();
            $table->string('down')->nullable();
            $table->text('strategies')->nullable();
            $table->string('team_position')->nullable();
            $table->integer('expected_yard_gain')->nullable();
            $table->string('position_number')->nullable();
            $table->string('pkg')->nullable();
            $table->string('possession')->nullable();
            $table->index(['user_id', 'game_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websocket_scoreboards');
    }
};
