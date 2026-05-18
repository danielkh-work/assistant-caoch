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
        Schema::table('league_teams', function (Blueprint $table) {
            $table->unsignedInteger('won')->default(0);
            $table->unsignedInteger('drawn')->default(0)->after('won');
            $table->unsignedInteger('lost')->default(0)->after('drawn');
            $table->unsignedInteger('points')->default(0)->after('lost');
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
        try {
        Schema::table('league_teams', function (Blueprint $table) {
             $table->dropColumn(['won', 'drawn', 'lost', 'points']);
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }
};
