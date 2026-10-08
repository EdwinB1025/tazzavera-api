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

        $this->command->info("Front client ID: {$client->id}");
    }
}
