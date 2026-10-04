<?php

use Database\Seeders\CertificationTypeSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(
    function () {
        $this->seed(RolesSeeder::class);
        $this->seed(CertificationTypeSeeder::class);
    }
);

test('guest_cannot_list_coffees', function () {

    $this->getJson('/coffees')
        ->assertUnauthorized();
});

test('authenticated_user_lists_coffees', function () {

    [, $token] = authenticate();

    createCoffee([], 3);

    /** Json response structure */

    $structure = [
        'data' => [
            '*' => [
                'ulid',
                'name',
                'roastLevel',
                'process',
                'variety',
                'producer',
                'country',
                'region',
                'altitude',
                'lot',
                'certifications',
            ],
        ],
    ];

    $this->withToken($token)
        ->getJson('/coffees')
        //->dump()
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure($structure);
});

test('authenticated_user_filters_coffees_by_name', function () {

    [, $token] = authenticate();

    $match = createCoffee(['name' => 'Cafes los andes']);
    createCoffee(['name' => 'Sidama Bensa']);

    $this->withToken($token)
        ->getJson('/coffees?name=andes')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $match->ulid);
});

test('authenticated_user_filters_coffees_without_results', function () {

    [, $token] = authenticate();

    createCoffee(['name' => 'Cafes los andes']);

    $this->withToken($token)
        ->getJson('/coffees?name=geisha')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('authenticated_user_filters_coffees_with_too_long_name', function () {

    [, $token] = authenticate();

    $this->withToken($token)
        ->getJson('/coffees?name=' . str_repeat('a', 151))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});
