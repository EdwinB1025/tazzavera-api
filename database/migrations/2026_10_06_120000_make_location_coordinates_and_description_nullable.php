<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * EDB 10/06/26: the front computes the coordinates and may send none; the description is optional.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->change();
            $table->decimal('latitud', 10, 8)->nullable()->change();
            $table->decimal('longitud', 11, 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('description', 255)->nullable(false)->change();
            $table->decimal('latitud', 10, 8)->nullable(false)->change();
            $table->decimal('longitud', 11, 8)->nullable(false)->change();
        });
    }
};
