<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grants additional head coaches access to a league.
     * Ownership remains on leagues.user_id; rows here are for shared access only.
     */
    public function up(): void
    {
        if (Schema::hasTable('league_access')) {
            return;
        }

        Schema::create('league_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('user_id');
            $table->string('access_type')->default('shared');
            $table->timestamps();

            $table->foreign('league_id')->references('id')->on('leagues')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['league_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_access');
    }
};
