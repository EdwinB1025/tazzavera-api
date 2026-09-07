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
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certification_type_id')->constrained()->cascadeOnDelete();
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['coffee_id', 'certification_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
