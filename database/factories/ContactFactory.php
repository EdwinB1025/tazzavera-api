<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_primary' => false,
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'web' => fake()->url(),
            'social' => '@' . fake()->userName(),
            'address' => fake()->streetAddress(),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
        ];
    }
}
