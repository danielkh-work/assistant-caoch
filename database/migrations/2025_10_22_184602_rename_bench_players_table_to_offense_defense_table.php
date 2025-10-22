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
         Schema::rename('bench_players', 'offense_defense_players');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::rename('offense_defense_players', 'bench_players');
    }
};
