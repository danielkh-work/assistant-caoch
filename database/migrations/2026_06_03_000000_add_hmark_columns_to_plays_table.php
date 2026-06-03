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
        Schema::table('plays', function (Blueprint $table) {
            $table->renameColumn('image', 'hmark_center');
        });

        Schema::table('plays', function (Blueprint $table) {
            $table->string('hmark_left')->nullable()->after('hmark_center');
            $table->string('hmark_right')->nullable()->after('hmark_left');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropColumn(['hmark_left', 'hmark_right']);
        });

        Schema::table('plays', function (Blueprint $table) {
            $table->renameColumn('hmark_center', 'image');
        });
    }
};
