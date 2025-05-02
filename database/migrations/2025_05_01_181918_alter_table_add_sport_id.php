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
      
        Schema::table('league_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('sport_id')->nullable()->constrained('sports')->onDelete('cascade');
        });
        Schema::table('league_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('sport_id')->nullable()->constrained('sports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
