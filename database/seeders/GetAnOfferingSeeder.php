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
        //**EDB 09/16/26: Creating i coffeeshops */
        for ($i = 1; $i <= 15; $i++) {
            $locations = mt_rand(1, 6);
            $this->callWith(LocationSeeder::class, ['locations' => $locations]);
        }
        $this->call(CreateOfferingSeeder::class);

        Offering::factory()->count(10)->create();
    }
}
