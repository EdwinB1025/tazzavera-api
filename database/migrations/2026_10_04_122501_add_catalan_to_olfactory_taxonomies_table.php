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
        Schema::table('olfactory_taxonomies', function (Blueprint $table) {
            $table->string('name_ca', 60)->after('name_es');
            $table->string('description_ca', 250)->nullable()->after('description_es');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olfactory_taxonomies', function (Blueprint $table) {
            $table->dropColumn(['name_ca', 'description_ca']);
        });
    }
};
