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
        Schema::create('defensive_play_personal_grouping', function (Blueprint $table) {
            $table->id();
            $table->integer('defensive_play_id');
            $table->unsignedBigInteger('personal_grouping_id'); 
            $table->foreign('defensive_play_id')
                    ->references('id')
                    ->on('defensive_plays')
                    ->cascadeOnDelete();

            $table->foreign('personal_grouping_id')
                    ->references('id')
                    ->on('personal_groupings')
                    ->cascadeOnDelete();        
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defensive_play_personal_grouping');
    }
};
