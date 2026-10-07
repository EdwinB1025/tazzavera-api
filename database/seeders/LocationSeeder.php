<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * EDB 10/06/26: coffee shops from a closed list of real specialty coffee businesses in Barcelona
     * (database/seeders/data/coffeeshops-barcelona.json): one coffeeshop user per business, named after it,
     * with 1 to 4 locations and each location's primary contact. No network: coordinates come from the file.
     */
    public function run(): void
    {
        $coffeeshops = json_decode(
            file_get_contents(database_path('seeders/data/coffeeshops-barcelona.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        foreach ($coffeeshops as $coffeeshop) {
            $user = User::factory()->assignRole('coffeeshop')->create([
                'name' => $coffeeshop['name'],
                'surname' => 'Coffeeshop',
                'email' => Str::slug($coffeeshop['name']) . '@tazavera.test',
            ]);

            foreach ($coffeeshop['locations'] as $data) {
                //EDB 10/06/26: Location::create, not the factory, which adds a random primary contact
                $location = Location::create([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'latitud' => $data['latitud'],
                    'longitud' => $data['longitud'],
                ]);

                $location->contacts()->create([
                    'is_primary' => true,
                    'web' => $coffeeshop['web'] ?? null,
                    'address' => $data['address'],
                    'city' => 'Barcelona',
                    'country' => 'España',
                    'postal_code' => $data['postal_code'],
                ]);
            }
        }
    }
}
