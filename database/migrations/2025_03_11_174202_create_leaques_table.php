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
        Schema::create('leaques', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('number_of_downs')->nullable();
            $table->string('length_of_field')->nullable(); // Dropdown
            $table->integer('number_of_timeouts')->nullable();
            $table->string('clock_time')->nullable()->comment("'CFL', 'NFL', 'Other'");
            $table->integer('number_of_quarters')->nullable();
            $table->integer('length_of_quarters')->nullable();
            $table->integer('stop_time_reason')->nullable(); // List of penalties and yardage
            $table->integer('overtime_rules')->nullable();
            $table->integer('number_of_players')->nullable();
            $table->string('flag_tbd')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaques');
    }
};
