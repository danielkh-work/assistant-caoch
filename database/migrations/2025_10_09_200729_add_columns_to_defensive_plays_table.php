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
        Schema::table('defensive_plays', function (Blueprint $table) {
            $table->string('strategies')->nullable();
            $table->string('preferred_down')->nullable();
            $table->string('min_expected_yard')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defensive_plays', function (Blueprint $table) {
            $table->dropColumn(['strategies', 'preferred_down', 'min_expected_yard']); 
        });
    }
};
