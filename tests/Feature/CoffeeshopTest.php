<?php

use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

test('coffeeshop_retrieves_locations', function () {

    [, $token] = authenticate('coffeeshop');

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
        ->assertOk()
        ->assertJsonStructure($structure);
});
