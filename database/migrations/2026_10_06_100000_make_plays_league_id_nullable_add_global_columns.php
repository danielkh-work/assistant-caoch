<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // league_id never actually had a working FK in this DB (confirmed: zero FKs
        // currently reference it - the original migration's ->constrained() call
        // silently never took effect, likely from this same int-vs-bigint mismatch).
        // We're not fixing that history, just making the column nullable, adding
        // the two new columns, and giving league_id a real FK for the first time
        // now that we're touching it anyway - matching the actual column types.
        Schema::table('plays', function (Blueprint $table) {
            $table->integer('league_id')->nullable()->change();
            $table->boolean('is_global')->default(false)->after('created_by');
            $table->integer('created_by_user_id')->nullable()->after('is_global');

            $table->foreign('league_id')->references('id')->on('leagues')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index('is_global');
        });
    }

    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropForeign(['league_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['is_global', 'created_by_user_id']);
        });

        Schema::table('plays', function (Blueprint $table) {
            $table->integer('league_id')->nullable(false)->change();
        });
    }
};
