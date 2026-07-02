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
        Schema::table('games', function (Blueprint $table) {
            $table->string('status')->nullable()->after('descrptions');
            $table->dateTime('match_start_date')->nullable()->after('status');
            $table->dateTime('match_end_date')->nullable()->after('match_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['status', 'match_start_date', 'match_end_date']);
        });
    }
};
