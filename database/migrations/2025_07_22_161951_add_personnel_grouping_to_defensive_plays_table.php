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
       Schema::table('defensive_plays', function (Blueprint $table) {
            $table->string('opponent_personnel_grouping')->nullable()->after('id');

            // $table->foreignId('defensive_play_parameter_id')
            //       ->nullable()
            //       ->constrained('defensive_play_parameters')
            //       ->onDelete('cascade')
            //       ->after('opponent_personnel_grouping');
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
        Schema::table('defensive_plays', function (Blueprint $table) {
            //  $table->dropForeign(['defensive_play_parameter_id']);
            $table->dropColumn('opponent_personnel_grouping');
            // $table->dropColumn('defensive_play_parameter_id');
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }
};
