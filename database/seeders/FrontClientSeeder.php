<?php

namespace Database\Seeders;

use App\Models\Passport\Client;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class FrontClientSeeder extends Seeder
{
    public function run(ClientRepository $clients): void
    {
        $client = Client::front()->first()
            ?? $clients->createAuthorizationCodeGrantClient(
                name: Client::FRONT,
                redirectUris: [env('FRONT_URL', 'https://localhost:3000') . '/callback'],
                confidential: false,
            );

        /** EDB 10/08/26: the front also signs a new user in right after registration (password grant, AUT R44) */
        if (! $client->hasGrantType('password')) {
            $client->forceFill(['grant_types' => [...$client->grant_types, 'password']])->save();
        }

        $this->command->info("Front client ID: {$client->id}");
    }
}
