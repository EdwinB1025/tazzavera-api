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
        Schema::create('evaluation_tastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_ref')->constrained('olfactory_taxonomies')->restrictOnDelete();
            $table->enum('type', ['main_tastes', 'defects', 'fragrance', 'aroma', 'flavor', 'aftertaste', 'mouthfeel']);
            $table->timestamps();
            $table->unique(['evaluation_id', 'taxonomy_ref', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_tastes');
    }
};
