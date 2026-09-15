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
        return [
            'coffee_inventory_id' => CoffeeInventory::inRandomOrder()->first()->id,
            'location_id' => function ($attributes) {
                $locations = Offering::where('coffee_inventory_id', $attributes['coffee_inventory_id'])->pluck('location_id');
                return Location::whereNotIn('id', $locations)
                    ->inRandomOrder()
                    ->first()
                    ->id;
            },
        ];
    }
}
