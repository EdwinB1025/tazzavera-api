<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

/** EDB 10/12/26: the migration that adds business_name keeps the public name of the coffee shops that already exist */
test('business_name_migration_fills_the_existing_coffeeshops', function () {
    $migration = require database_path('migrations/2026_10_12_120000_add_business_name_to_users_table.php');

    $coffeeshop = User::factory()->assignRole('coffeeshop')->create(['name' => 'Nomad Coffee']);
    $specialist = User::factory()->assignRole('specialist')->create(['name' => 'Andrés']);
    $user = User::factory()->assignRole('user')->create(['name' => 'Laura']);

    $migration->down();
    $migration->up();

    expect(DB::table('users')->where('id', $coffeeshop->id)->value('business_name'))->toBe('Nomad Coffee')
        ->and(DB::table('users')->where('id', $specialist->id)->value('business_name'))->toBeNull()
        ->and(DB::table('users')->where('id', $user->id)->value('business_name'))->toBeNull();
});
