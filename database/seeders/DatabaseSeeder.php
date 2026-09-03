<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    //use WithoutModelEvents; EDB 09/03/26: avoid the custom trait to generate ulid based on the event creating.

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                RolesSeeder::class,
                //UserSeeder::class,
                //LocationSeeder::class,
                OlfactoryTaxonomySeeder::class,
            ]
        );
    }
}
