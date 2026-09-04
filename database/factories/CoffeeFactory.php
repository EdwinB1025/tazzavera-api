<?php

namespace Database\Factories;

use App\Enums\RoastLevel;
use App\Models\CertificationType;
use App\Models\Coffee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coffee>
 */
class CoffeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'roast_level' => fake()->randomElement(RoastLevel::cases()),
            'process' => fake()->randomElement(['washed', 'natural', 'honey', 'anaerobic', 'wet_hulled']),
            'variety' => fake()->randomElement(['Caturra', 'Bourbon', 'Typica', 'Geisha', 'Catuai', 'SL28', 'Pacamara']),
            'country' => fake()->randomElement(['Etiopía', 'Colombia', 'Kenia', 'Brasil', 'Guatemala', 'Costa Rica', 'Panamá']),
            'region' => fake()->optional()->city(),
            'altitude' => fake()->numberBetween(1000, 2200),
            'lot' => fake()->year() . '-' . str_pad(fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Coffee $coffee) {
            $count = fake()->numberBetween(0, 3);

            if ($count === 0) {
                return;
            }

            CertificationType::inRandomOrder()->limit($count)->get()
                ->each(function ($type) use ($coffee) {
                    $issued = fake()->dateTimeBetween('-3 years', 'now');
                    $coffee->certificationTypes()->attach($type->id, [
                        'issued_at' => $issued,
                        'expires_at' => $type->code === 'cup_of_excellence'
                            ? null
                            : (clone $issued)->modify('+3 years'),
                    ]);
                });
        });
    }
}
