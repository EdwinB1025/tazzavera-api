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
        Schema::create('olfactory_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('olfactory_taxonomies')->cascadeOnUpdate()->restrictOnDelete();
            $table->tinyInteger('level');
            $table->string('name_en', 60);
            $table->string('name_es', 60);
            $table->string('description_en', 250)->nullable();
            $table->string('description_es', 250)->nullable();
            $table->char('color_base', 7)->nullable();
            $table->char('color', 7)->nullable();
            $table->json('categories')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olfactory_taxonomies');
    }
};
