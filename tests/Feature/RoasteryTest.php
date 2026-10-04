<?php

use Database\Seeders\RolesSeeder;

beforeEach(
    function () {
        $this->seed(RolesSeeder::class);
    }
);

test('public_lists_roasteries_without_token', function () {

    createRoastery([], [], 2);

    $this->getJson('/roasteries')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('authenticated_user_lists_roasteries', function () {

    [, $token] = authenticate();

    createRoastery([], [], 3);

    /** Json response structure */

    $structure = [
        'data' => [
            '*' => [
                'ulid',
                'name',
                'description',
            ],
        ],
    ];

    $this->withToken($token)
        ->getJson('/roasteries')
        //->dump()
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure($structure);
});

test('authenticated_user_filters_roasteries_by_name', function () {

    [, $token] = authenticate();

    $match = createRoastery(['name' => 'Nomada Roasters']);
    createRoastery(['name' => 'Right Side Coffee']);

    $this->withToken($token)
        ->getJson('/roasteries?name=nomada')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $match->ulid);
});

test('authenticated_user_filters_roasteries_without_results', function () {

    [, $token] = authenticate();

    createRoastery(['name' => 'Nomada Roasters']);

    $this->withToken($token)
        ->getJson('/roasteries?name=syra')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('authenticated_user_filters_roasteries_with_too_long_name', function () {

    [, $token] = authenticate();

    $this->withToken($token)
        ->getJson('/roasteries?name=' . str_repeat('a', 151))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});
