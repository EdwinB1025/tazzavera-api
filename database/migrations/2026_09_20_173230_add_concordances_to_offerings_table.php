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
        Schema::table('offerings', function (Blueprint $table) {
            $table->dropColumn('concordance');
            $table->decimal('concordance_affective', 4, 3)->nullable();
            $table->decimal('concordance_descriptive', 4, 3)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offerings', function (Blueprint $table) {
            $table->dropColumn(['concordance_affective', 'concordance_descriptive']);
            $table->decimal('concordance', 4, 3)->nullable();
        });
    }
};
