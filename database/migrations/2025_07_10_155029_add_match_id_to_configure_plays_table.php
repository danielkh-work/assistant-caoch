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
        Schema::table('configure_plays', function (Blueprint $table) {
             $table->unsignedBigInteger('match_id')->nullable()->after('league_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configure_plays', function (Blueprint $table) {
             $table->dropColumn('match_id');
        });
    }
};
