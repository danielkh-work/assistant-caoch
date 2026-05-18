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
        Schema::table('players', function (Blueprint $table) {
            $table->string('position_value')->nullable()->change();
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
        Schema::table('players', function (Blueprint $table) {
            $table->string('position_value')->nullable(false)->change();
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }
};
