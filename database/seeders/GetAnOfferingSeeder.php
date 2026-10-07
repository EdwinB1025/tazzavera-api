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
        //**EDB 10/06/26: the Barcelona coffee shops of the data file */
        $this->call(LocationSeeder::class);
        $this->call(CreateOfferingSeeder::class);

        //EDB 10/06/26: one by one, so each factory definition sees the offerings already stored (count(10) builds all ten pairs first and could repeat one)
        for ($i = 1; $i <= 10; $i++) {
            Offering::factory()->create();
        }
    }
}
