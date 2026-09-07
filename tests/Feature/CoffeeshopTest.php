<?php

use App\Models\Location;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

test('coffeeshop_retrieves_locations', function () {

    [$user, $token] = authenticate('coffeeshop');

    Location::factory()->count(3)->create(['user_id' => $user->id]);

    $structure = [
        'data' => [
            '*' => [
                'ulid',
                'name',
                'description',
                'latitud',
                'longitud',
                'contacts' => [
                    '*' => [
                        'ulid',
                        'is_primary',
                        'phone',
                        'email',
                        'web',
                        'social',
                        'address',
                        'country',
                        'city',
                        'postal_code',
                    ],
                ],
            ],
        ]
    ];

    $this->withToken($token)
        ->getJson('/locations')
        ->dump()
        ->assertOk()
        ->assertJsonStructure($structure);
});
