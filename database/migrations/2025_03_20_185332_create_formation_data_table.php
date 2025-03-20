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
        Schema::create('formation_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formation_id')->constrained('formations')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('y')->nullable();
            $table->string('x')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formation_data');
    }
};
