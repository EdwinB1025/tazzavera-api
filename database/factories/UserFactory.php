<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Override;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'surname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    #[Override]
    public function configure()
    {
        return $this->afterCreating(
            fn($user) => $user->contacts()->save(Contact::factory()->make(['is_primary' => true]))
        );
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function registrationPayload(string $role = 'user'): array
    {
        $password = $this->createPassword();

        return [
            'name' => fake()->name(),
            'surname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => $password,
            'password_confirmation' => $password,
            'role' => $role,
        ];
    }

    public function createPassword(): string
    {
        do {
            $password = Str::password(8, true, true, false);
        } while (
            Validator::make(
                ['password' => $password],
                ['password' => Password::defaults()],
            )->fails()
        );
        return $password;
    }

    /**Assigning the role 'coffeeshop' */
    public function assignRole(string $role = 'user'): static
    {
        /**EDB 09/03/26: the model parameter is passed by laravel directly, it works for an array of models */
        return $this->afterCreating(fn($user) => $user->assignRole($role));
    }
}
