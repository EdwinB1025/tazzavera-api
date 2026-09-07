<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $locations = 1): void
    {
        User::factory()
            ->assignRole('coffeeshop')
            ->has(
                Location::factory()
                    ->count($locations)
                    ->has(Contact::factory()->state(['is_primary' => true]), 'contacts'),
                'locations'
            )->create();
    }
}
