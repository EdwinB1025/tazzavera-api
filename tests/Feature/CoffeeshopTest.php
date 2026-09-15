<?php

use App\Models\CertificationType;
use App\Models\Coffee;
use App\Models\CoffeeInventory;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Offering;
use App\Models\Roastery;
use Database\Seeders\CertificationTypeSeeder;
use Database\Seeders\CreateOfferingSeeder;
use Database\Seeders\GetAnOfferingSeeder;
use Database\Seeders\OlfactoryTaxonomySeeder;
use Database\Seeders\RolesSeeder;

beforeEach(
    function () {
        $this->seed(RolesSeeder::class);
        $this->seed(CertificationTypeSeeder::class);
        $this->seed(OlfactoryTaxonomySeeder::class);
    }
);

test('coffeeshop_retrieves_locations', function () {

    [$user, $token] = authenticate('coffeeshop');

    createLocation(['user_id' => $user->id]);

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
                        'isPrimary',
                        'phone',
                        'email',
                        'web',
                        'social',
                        'address',
                        'country',
                        'city',
                        'postalCode',
                    ],
                ],
            ],
        ]
    ];

    $this->withToken($token)
        ->getJson('/locations')
        //->dump()
        ->assertOk()
        ->assertJsonStructure($structure);
});

test('coffeeshop_filters_coffee_inventory', function ($model, $field, $value, $queryParm) {

    [, $token] = authenticate('coffeeshop');

    //Create coffeeInventory

    $rawCoffee = ($model === 'coffee') ? [$field => $value] : [];
    $contactRoastery = ($model === 'roastery') ? [$field => $value] : [];

    $coffee = createCoffee($rawCoffee);
    $roastery = createRoastery([], $contactRoastery);

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
                'roastLot',
                'productionDate',
                'coffee' => [
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
        //->dump()
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

    $coffee = createCoffee([
        'name' => 'Cafes los andes',
        'country' => 'Colombia',
        'process' => 'honey',
    ]);
    $roastery = createRoastery();
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

test('coffeeshop_creates_offering', function ($count) {
    [$user, $token] = authenticate('coffeeshop');

    $locations = createLocation(['user_id' => $user->id], [], 5)
        ->random($count)
        ->pluck('ulid')
        ->all();

    $this->seed(CreateOfferingSeeder::class);

    $coffeeInventory = CoffeeInventory::inRandomOrder()->first()->ulid;

    $this->withToken($token)
        ->postJson('/offerings', [
            'coffeeInventoryId' => $coffeeInventory,
            'locations' => $locations
        ])
        ->assertStatus(201);
})->with(
    [
        'Single Location' => 1,
        'Multiple Locations' => 3
    ]
);

test('anyone_retrieves_an_offering', function () {

    $this->seed(GetAnOfferingSeeder::class);

    $offeringId = Offering::inRandomOrder()->first()->ulid;

    $structure = [
        'data' => [
            'ulid',
            'evaluationCount',
            'defectiveEvaluationCount',
            'verificationStatus',
            'location' => [
                'ulid',
                'name',
                'description',
                'latitud',
                'longitud',
            ],
            'coffeeInventory' => [
                'ulid',
                'roastLot',
                'productionDate',
                'coffee',
                'roastery',
            ],
            'SensoryTaxonomy',
        ],
    ];

    $this->getJson("/offerings/{$offeringId}")
        //->dump()
        ->assertOk()
        ->assertJsonPath('data.ulid', $offeringId)
        ->assertJsonStructure($structure);
});

test('authenticated_coffeeshp_deletes_offering', function () {
    [$user, $token] = authenticateWithWriteScope();

    $this->seed(GetAnOfferingSeeder::class);

    $offering = Offering::inRandomOrder()->first();

    $this->withToken($token)
        ->deleteJson("/offerings/{$offering->ulid}")
        ->assertOK();

    $this->assertDatabaseMissing('offerings', ['id' => $offering->id]);
});
