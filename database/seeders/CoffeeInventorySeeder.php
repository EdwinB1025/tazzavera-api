<?php

namespace Database\Seeders;

use App\Models\CoffeeInventory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoffeeInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($count = null): void
    {
        CoffeeInventory::factory($count)->create();
    }
}
