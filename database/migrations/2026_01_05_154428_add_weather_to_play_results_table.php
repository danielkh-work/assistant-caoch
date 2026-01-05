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
        Schema::table('play_results', function (Blueprint $table) {
             $table->enum('weather', ['none', 'rain', 'snow'])
                  ->default('none')
                  ->after('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_results', function (Blueprint $table) {
            $table->dropColumn('weather');
        });
    }
};
