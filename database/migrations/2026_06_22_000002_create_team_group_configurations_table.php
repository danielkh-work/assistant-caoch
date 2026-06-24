<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_group_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('team_group_id')->index();
            $table->timestamps();

            $table->unique(['team_id', 'team_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_group_configurations');
    }
};
