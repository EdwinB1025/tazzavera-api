<?php

namespace Database\Seeders;

use App\Models\Offering;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GetAnOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $locations = mt_rand(1, 8);
            $this->callWith(LocationSeeder::class, ['locations' => $locations]);
        }
        $this->call(CreateOfferingSeeder::class);

        Offering::factory()->count(10)->create();
    }
}
