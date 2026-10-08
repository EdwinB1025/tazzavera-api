<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
     * EDB 10/08/26: backfill the nulls first, otherwise the NOT NULL change fails on existing rows.
     */
    public function down(): void
    {
        DB::table('locations')->whereNull('description')->update(['description' => '']);
        DB::table('locations')->whereNull('latitud')->update(['latitud' => 0]);
        DB::table('locations')->whereNull('longitud')->update(['longitud' => 0]);

        Schema::table('locations', function (Blueprint $table) {
            $table->string('description', 255)->nullable(false)->change();
            $table->decimal('latitud', 10, 8)->nullable(false)->change();
            $table->decimal('longitud', 11, 8)->nullable(false)->change();
        });
    }
};
