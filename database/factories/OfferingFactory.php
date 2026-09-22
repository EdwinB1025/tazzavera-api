<?php

namespace Database\Factories;

use App\Models\CoffeeInventory;
use App\Models\Location;
use App\Models\Offering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offering>
 */
class OfferingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $locationsId = null;
        while (! $locationsId) {
            $coffeeInventoryId = CoffeeInventory::inRandomOrder()->value('id');
            $locationsId =  Location::whereDoesntHave(
                'offerings',
                function ($query) use ($coffeeInventoryId) {
                    $query->where('coffee_inventory_id', $coffeeInventoryId);
                }
            )->inRandomOrder()->value('id');
        }


        return [
            'coffee_inventory_id' => $coffeeInventoryId,
            'location_id' => $locationsId,
            'evaluation_count' => 0,
            'defective_evaluation_count' => 0,
            'cupping_avg' => null,
            'fragrance_avg' => null,
            'aroma_avg' => null,
            'flavor_avg' => null,
            'aftertaste_avg' => null,
            'acidity_avg' => null,
            'sweetness_avg' => null,
            'mouthfeel_avg' => null,
            'overall_avg' => null,
            'concordance_affective' => null,
            'concordance_descriptive' => null,
            'verification_status' => 'provisional',
        ];
    }
}
