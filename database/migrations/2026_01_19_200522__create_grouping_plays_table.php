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
       Schema::create('personal_grouping_play', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('personal_grouping_id'); // matches personal_groupings.id (BIGINT unsigned)

                $table->integer('play_id'); // matches plays.id (INT signed)

                $table->timestamps();

                $table->foreign('personal_grouping_id')
                    ->references('id')
                    ->on('personal_groupings')
                    ->cascadeOnDelete();

                $table->foreign('play_id')
                    ->references('id')
                    ->on('plays')
                    ->cascadeOnDelete();

                $table->unique(['personal_grouping_id', 'play_id']);
        });

    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_grouping_play');
    }
};
