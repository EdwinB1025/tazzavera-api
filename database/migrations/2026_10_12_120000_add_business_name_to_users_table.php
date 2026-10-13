<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * EDB 10/12/26: a coffee shop's public name is its business name, not the owner's first name (Product owner, option A).
     * The coffee shops that already exist keep their current public name: business_name is filled with name.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_name', 150)->nullable()->after('surname');
        });

        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('model_has_roles.model_id')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', Relation::getMorphAlias(User::class))
                    ->where('roles.name', 'coffeeshop');
            })
            ->update(['business_name' => DB::raw('name')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('business_name');
        });
    }
};
