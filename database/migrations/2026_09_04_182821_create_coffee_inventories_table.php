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
        Schema::create('coffee_inventory', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('roastery_id')->constrained()->restrictOnDelete();
            $table->foreignId('coffee_id')->constrained()->restrictOnDelete();
            $table->string('roast_lot', 60)->nullable();
            $table->date('production_date');
            $table->timestamps();
            $table->unique(['roastery_id', 'coffee_id', 'roast_lot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_inventory');
    }
};
