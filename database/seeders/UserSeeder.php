<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($count = 1, $role = 'user'): void
    {
        User::factory()->count($count)->assignRole($role)->create();
    }
}
