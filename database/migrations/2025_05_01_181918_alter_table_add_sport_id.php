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
      
        try {
        Schema::table('league_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('sport_id')->nullable()->constrained('sports')->onDelete('cascade');
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
        try {
        Schema::table('league_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('sport_id')->nullable()->constrained('sports')->onDelete('cascade');
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
