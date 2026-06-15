<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'league_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('league_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->integer('league_id')->nullable()->after('head_coach_id');
            $table->foreign('league_id')->references('id')->on('leagues')->nullOnDelete();
        });

        $qbUsers = DB::table('users')
            ->where('role', 'qb')
            ->whereNull('league_id')
            ->get();

        foreach ($qbUsers as $qb) {
            $oldestLeagueId = DB::table('leagues')
                ->where('user_id', $qb->head_coach_id)
                ->orderBy('id')
                ->value('id');

            if ($oldestLeagueId !== null) {
                DB::table('users')
                    ->where('id', $qb->id)
                    ->update(['league_id' => $oldestLeagueId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['league_id']);
            $table->dropColumn('league_id');
        });
    }
};
