<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * EDB 10/07/26: an offering with evaluations can be deleted; specialists' evaluations are kept without an offering.
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['offering_id']);
            $table->unsignedBigInteger('offering_id')->nullable()->change();
            $table->foreign('offering_id')->references('id')->on('offerings')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['offering_id']);
            $table->unsignedBigInteger('offering_id')->nullable(false)->change();
            $table->foreign('offering_id')->references('id')->on('offerings')->restrictOnDelete();
        });
    }
};
