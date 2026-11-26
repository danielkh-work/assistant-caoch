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
            // Nullable on purpose: existing plays stay untagged until someone
            // picks 11 or 12 on them - we don't guess that for them.
            $table->unsignedTinyInteger('player_count')->nullable()->after('is_global');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropColumn('player_count');
        });
    }
};
