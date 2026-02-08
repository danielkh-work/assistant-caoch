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
        Schema::create('offensive_target_strengths', function (Blueprint $table) {
            $table->id();
            $table->integer('play_id');
            $table->string('code');
            $table->integer('strength')->default(0);
            $table->integer('target_offensive_id');
            $table->integer('target_defensive_id');
            $table->integer('total_strength')->default(0);
            $table->foreign('play_id')->references('id')->on('plays')->onDelete('cascade');
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offensive_target_strengths');
    }
};
