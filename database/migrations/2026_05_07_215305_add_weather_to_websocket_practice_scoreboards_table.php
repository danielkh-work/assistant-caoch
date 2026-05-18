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
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            $table->string('weather')->nullable()->default('Normal')->after('possession');
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
        Schema::table('websocket_practice_scoreboards', function (Blueprint $table) {
            $table->dropColumn('weather');
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }
};
