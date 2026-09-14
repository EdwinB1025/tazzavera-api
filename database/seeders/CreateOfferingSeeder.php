<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->callWith(RoasterySeeder::class, ['count' => 5]);
        $this->callWith(CoffeeSeeder::class, ['count' => 15]);
        $this->callWith(CoffeeInventorySeeder::class, ['count' => 12]);
    }
}
