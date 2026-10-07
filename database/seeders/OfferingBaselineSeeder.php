<?php

namespace Database\Seeders;

use App\Models\CoffeeInventory;
use App\Models\Evaluation;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfferingBaselineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 1 to 5 offerings per existing coffee shop, spread over its locations, each with
     * the coffee shop's own closed baseline evaluation (its provisional evaluation).
     * Uses the coffee shops, locations and inventory created by the DatabaseSeeder seeders.
     */
    public function run(): void
    {
        $inventoryIds = CoffeeInventory::pluck('id');

        User::role('coffeeshop')->with('locations')->get()->each(function (User $coffeeshop) use ($inventoryIds) {
            if ($coffeeshop->locations->isEmpty()) {
                return;
            }

            $count = mt_rand(1, 5);
            $unavailableInventory = $coffeeshop->offerings()->pluck('coffee_inventory_id');

            for ($i = 0; $i < $count; $i++) {
                $available = $inventoryIds->diff($unavailableInventory);

                if ($available->isEmpty()) {
                    break;
                }

                $inventoryId = $available->random();

                foreach ($coffeeshop->locations as $location) {
                    Offering::factory()->create([
                        'location_id'         => $location->id,
                        'coffee_inventory_id' => $inventoryId,
                    ]);
                }

                $unavailableInventory->push($inventoryId);
            }
        });
    }
}
