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
        Schema::create('grouping_plays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_grouping_id');
             $table->foreignId('personal_grouping_id')
                  ->constrained('personal_grouping') // must match table name exactly
                  ->onDelete('cascade');

            // Foreign key to plays
            $table->foreignId('play_id')
                  ->constrained('plays')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grouping_plays');
    }
};
