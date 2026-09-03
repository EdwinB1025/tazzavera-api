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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique()->after('id');
            $table->string('contactable_type', 60);
            $table->unsignedBigInteger('contactable_id');
            $table->boolean('is_primary')->default(false);
            $table->string('phone', 25)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('web', 255)->nullable();
            $table->string('social', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('country', 60)->nullable();
            $table->string('city', 90)->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->timestamps();

            $table->index(['contactable_type', 'contactable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
