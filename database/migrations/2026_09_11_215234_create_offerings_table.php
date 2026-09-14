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
        Schema::create('offerings', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->restrictOnDelete();
            $table->foreignId('coffee_inventory_id')
                ->constrained('coffee_inventory')
                ->restrictOnDelete();
            $table->unsignedInteger('evaluation_count')->default(0);
            $table->unsignedInteger('defective_evaluation_count')->default(0);
            $table->decimal('cupping_avg', 4, 2)->nullable();
            $table->decimal('fragrance_avg', 3, 1)->nullable();
            $table->decimal('aroma_avg', 3, 1)->nullable();
            $table->decimal('flavor_avg', 3, 1)->nullable();
            $table->decimal('aftertaste_avg', 3, 1)->nullable();
            $table->decimal('acidity_avg', 3, 1)->nullable();
            $table->decimal('sweetness_avg', 3, 1)->nullable();
            $table->decimal('mouthfeel_avg', 3, 1)->nullable();
            $table->decimal('overall_avg', 3, 1)->nullable();
            $table->decimal('concordance', 4, 3)->nullable();
            $table->enum('verification_status', ['provisional', 'verified'])
                ->default('provisional');
            $table->timestamps();

            $table->unique(['location_id', 'coffee_inventory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offerings');
    }
};
