<?php

namespace Database\Factories;

use App\Models\Coffee;
use App\Models\CoffeeInventory;
use App\Models\Roastery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
        $coffeeId = Coffee::inRandomOrder()->first()?->id;
        $rosteryId = Roastery::inRandomOrder()->first()?->id;


        return [
            'roastery_id' => $rosteryId,
            'coffee_id' => $coffeeId,
            'production_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            //EDB 09/04/31: generate a new lot, if the combination exists already for the same day
            'roast_lot' => function (array $attributes) {
                $nextLot = CoffeeInventory::where('roastery_id', $attributes['roastery_id'])
                    ->where('coffee_id', $attributes['coffee_id'])
                    ->where('production_date', $attributes['production_date'])
                    ->count() + 1;

                return now()->format('Y-m-d') . '-' . $nextLot;
            },
        ];
    }
}
