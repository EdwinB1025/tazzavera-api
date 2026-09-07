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
        Schema::create('coffees', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 150);
            $table->enum('roast_level', ['light', 'medium_light', 'medium', 'medium_dark', 'dark'])->default('medium');
            $table->string('process', 60);
            $table->string('variety', 60);
            $table->string('country', 60);
            $table->string('region', 90)->nullable();
            $table->unsignedInteger('altitude')->nullable();
            $table->string('lot', 60)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffees');
    }
};
