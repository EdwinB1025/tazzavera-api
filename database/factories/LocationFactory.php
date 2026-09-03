<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->assignRole('coffeeshop'),
            'name' => fake()->company(),
            'description' => fake()->catchPhrase(),
            'latitud' => fake()->latitude(),
            'longitud' => fake()->longitude(),
        ];
    }
}
