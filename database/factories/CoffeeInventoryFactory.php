<?php

namespace Database\Factories;

use App\Models\Coffee;
use App\Models\CoffeeInventory;
use App\Models\Roastery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoffeeInventory>
 */
class CoffeeInventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'roastery_id' => Roastery::inRandomOrder()->first()?->id,
            'coffee_id' => Coffee::inRandomOrder()->first()?->id,
            'production_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            //EDB 09/04/31: generate a new lot, if the combination exists already for the same day
            'roast_lot' => fn(array $attributes) => $attributes['production_date'] . '-' . fake()->unique()->numberBetween(1, 999999),
        ];
    }
}
