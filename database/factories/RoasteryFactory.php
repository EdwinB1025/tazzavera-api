<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Roastery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Roastery>
 */
class RoasteryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => fake()->optional()->paragraph(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(
            fn($rostery) => $rostery->contacts()->save(Contact::factory()->make(['is_primary' => true]))
        );
    }
}
