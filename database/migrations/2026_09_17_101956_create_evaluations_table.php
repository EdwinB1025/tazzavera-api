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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('offering_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->enum('evaluator_type', ['specialist', 'baseline'])->default('specialist');
            $table->string('extraction_method', 60)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->decimal('cupping_score', 4, 2)->nullable(); //EDB 09/17/26: to be calculated as service during store
            $table->boolean('is_defective')->default(false);   //EDB 09/17/26: to be calculated as service during store
            $table->json('descriptive');
            $table->json('affective')->nullable();
            $table->json('extrinsics')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
