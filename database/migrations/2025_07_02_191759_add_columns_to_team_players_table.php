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
        Schema::table('team_players', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('number')->nullable()->after('name');
            $table->string('position')->nullable()->default('1')->after('number');
            $table->double('size')->after('position');
            $table->double('speed')->after('size');
            $table->integer('strength')->after('speed');
            $table->double('weight', 8, 2)->nullable()->after('strength');
            $table->double('height', 8, 2)->nullable()->after('weight');
            $table->date('dob')->nullable()->after('height');
            $table->string('image')->nullable()->after('dob');
            $table->string('position_value')->nullable()->after('image');
            $table->string('ofp')->nullable()->after('position_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_players', function (Blueprint $table) {
              $table->dropColumn([
                'name',
                'number', 'position', 'size',
                'speed', 'strength', 'weight', 'height',
                'dob', 'image', 'position_value', 'ofp'
            ]);
        });
    }
};
