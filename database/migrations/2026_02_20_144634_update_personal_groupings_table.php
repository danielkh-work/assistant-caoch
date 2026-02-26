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
        Schema::table('personal_groupings', function (Blueprint $table) {
             $table->json('players')->nullable()->change();

            $table->json('practice_players')->nullable()->after('players');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_groupings', function (Blueprint $table) {
            $table->json('players')->nullable(false)->change();
            $table->dropColumn('practice_players');
        });
    }
};
