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
        Schema::table('players', function (Blueprint $table) {
            $table->double('size')->nullable()->change();
            $table->integer('strength')->nullable()->change();
            $table->string('ofp')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->double('size')->nullable()->change(); 
            $table->integer('strength')->nullable()->change();
            $table->string('ofp')->nullable()->change();
        });
    }
};
