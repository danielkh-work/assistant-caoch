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
        Schema::table('offense_defense_players', function (Blueprint $table) {
            $table->enum('player_type', ['offence', 'deffence'])->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offense_defense_players', function (Blueprint $table) {
             $table->dropColumn('player_type');
        });
    }
};
