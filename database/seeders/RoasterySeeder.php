<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Roastery;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoasterySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($count = null): void
    {
        Roastery::factory($count)->create();
    }
}
