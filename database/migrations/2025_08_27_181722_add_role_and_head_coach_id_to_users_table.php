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
        Schema::table('users', function (Blueprint $table) {

            $table->enum('role', ['head_coach', 'assistant_coach'])->default('head_coach')->after('email');
            $table->unsignedBigInteger('head_coach_id')->nullable()->after('role');
            $table->foreign('head_coach_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['head_coach_id']);
            $table->dropColumn(['role', 'head_coach_id']);
        });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'already exists') === false) throw $e;
        }
    }
};
