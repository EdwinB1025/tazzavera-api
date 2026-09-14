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
        Schema::create('offering_tastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')
                ->constrained('offerings')
                ->cascadeOnDelete();
            $table->foreignId('taxonomy_ref')
                ->constrained('olfactory_taxonomies');
            $table->enum('type', [
                'main_tastes',
                'defects',
                'fragrance',
                'aroma',
                'flavor',
                'aftertaste',
                'mouthfeel',
            ]);
            $table->enum('level', ['0', '1', '2']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')
                ->references('id')
                ->on('offering_tastes')
                ->nullOnDelete();
            $table->unsignedInteger('count');
            $table->timestamps();

            $table->unique(['offering_id', 'taxonomy_ref', 'type']);
            $table->index('taxonomy_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_tastes');
    }
};
