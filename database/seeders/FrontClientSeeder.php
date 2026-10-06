<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class FrontClientSeeder extends Seeder
{
    public function run(ClientRepository $clients): void
    {
        $client = Client::where('name', 'tazavera-front')->first()
            ?? $clients->createAuthorizationCodeGrantClient(
                name: 'tazavera-front',
                redirectUris: ['http://localhost:3000/callback'],
                confidential: false,
            );

        $this->command->info("Front client ID: {$client->id}");
    }
}
