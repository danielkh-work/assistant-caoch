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
        Schema::table('plays', function (Blueprint $table) {
            DB::statement("
            ALTER TABLE plays 
            MODIFY offensive_play_type ENUM('run','pass','rpo','play_action')
           ");
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
        Schema::table('plays', function (Blueprint $table) {
        DB::statement("
            ALTER TABLE plays 
            MODIFY offensive_play_type ENUM('run','pass')
        ");
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }
};
  