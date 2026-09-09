<?php

use App\Models\Coffee;
use App\Models\CoffeeInventory;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Roastery;
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

test('coffeeshop_retrieves_coffee_inventory', function ($model, $field, $value, $queryParm) {

    [, $token] = authenticate('coffeeshop');

    //Create coffeeInventory

    $rawCoffee = ($model === 'coffee') ? [$field => $value] : null;
    $rawRoastery = ($model === 'roastery') ? [$field => $value] : null;

    $coffee = Coffee::factory()->create($rawCoffee);
    $roastery = Roastery::factory()
        ->has(
            Contact::factory()->state($rawRoastery),
            'contacts'
        )->create();

    CoffeeInventory::factory()
        ->create([
            'roastery_id' => $roastery->id,
            'coffee_id' => $coffee->id,
        ]);

    /** Json response structure */

    $structure = [
        'data' => [
            '*' => [
                'ulid',
                'roast_lot',
                'production_date',
                'coffee' => [
                    'ulid',
                    'name',
                    'roast_level',
                    'process',
                    'variety',
                    'producer',
                    'country',
                    'region',
                    'altitude',
                    'lot',
                ],
                'roastery' => [
                    'ulid',
                    'name',
                    'description',
                ],
            ],
        ],
    ];

    /**Request with filter */
    $this->withToken($token)
        ->getJson("/coffeeInventory?{$queryParm}={$value}")
        ->dump()
        ->assertOk()
        ->assertJsonStructure($structure);
})->with([
    // [modelo, columna_bd, valor, query_param]
    'coffeeName'    => ['coffee', 'name', 'Cafes los andes', 'coffeeName'],
    'originCountry' => ['coffee', 'country', 'Colombia', 'originCountry'],
    'originRegion'  => ['coffee', 'region', 'Huila', 'originRegion'],
    'process'       => ['coffee', 'process', 'honey', 'process'],
    'producer'      => ['coffee', 'producer', 'finca el atril', 'producer'],
    'city'          => ['roastery', 'city', 'Barcelona', 'city'],
]);

test('coffeeshop_filters_coffee_inventory_combined', function (array $filters) {
    [$user, $token] = authenticate('coffeeshop');

    $coffee = Coffee::factory()->create([
        'name' => 'Cafes los andes',
        'country' => 'Colombia',
        'process' => 'honey',
    ]);
    $roastery = Roastery::factory()->create();
    CoffeeInventory::factory()->create([
        'coffee_id' => $coffee->id,
        'roastery_id' => $roastery->id,
    ]);


    $query = http_build_query($filters);

    $this->withToken($token)
        ->getJson("/coffeeInventory?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
})->with([
    'name + country'          => [['coffeeName' => 'Cafes los andes', 'originCountry' => 'Colombia']],
    'country + process'       => [['originCountry' => 'Colombia', 'process' => 'honey']],
    'name + country + process' => [['coffeeName' => 'Cafes los andes', 'originCountry' => 'Colombia', 'process' => 'honey']],
]);
