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
        Schema::create('axis_concordances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained()->cascadeOnDelete();
            $table->enum('cva_type', ['descriptive', 'affective']);
            $table->string('axis', 30);
            $table->decimal('value', 4, 3);
            $table->timestamps();

            $table->unique(['offering_id', 'cva_type', 'axis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('axis_concordances');
    }
};
