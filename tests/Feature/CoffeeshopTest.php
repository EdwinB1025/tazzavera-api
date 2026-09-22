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
            'sensoryTaxonomy',
        ],
    ];

    $this->getJson("/offerings/{$offeringId}")
        //->dump()
        ->assertOk()
        ->assertJsonPath('data.ulid', $offeringId)
        ->assertJsonStructure($structure);
});

test('authenticated_coffeeshop_deletes_offering', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');

    $offering = createOfferingsForUser($user, 1, 1);

    $this->withToken($token)
        ->deleteJson("/offerings/{$offering->ulid}")
        ->assertOK();

    $this->assertDatabaseMissing('offerings', ['id' => $offering->id]);
});

test('authenticated_coffeeshop_deletes_offerings', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);

    $offerings = createOfferingsForUser($user, 4, 4)->random(3);


    $offeringsIds = $offerings->pluck('ulid')->toArray();

    $this->withToken($token)
        ->deleteJson("/offerings", ['offerings' => $offeringsIds])
        ->assertOK();

    foreach ($offerings as $offering) {
        $this->assertDatabaseMissing('offerings', ['id' => $offering->id]);
    }
});

test('authenticated_coffeeshop_deletes_offerings_without_ownership', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);

    $offerings = Offering::inRandomOrder()->limit(3)->get();

    $offeringsIds = $offerings->pluck('ulid')->toArray();

    $this->withToken($token)
        ->deleteJson("/offerings", ['offerings' => $offeringsIds])
        ->assertForbidden();
});

test('authenticated_coffeeshop_deletes_offering_without_scope', function () {
    [$user, $token] = authenticate('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);

    $offering = createOfferingsForUser($user, 1, 1);

    $this->withToken($token)
        ->deleteJson("/offerings/{$offering->ulid}")
        ->assertForbidden();
});

test('filter_offerings_by_ranges_and_relations', function () {
    [$owner] = authenticate('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);


    $match = Offering::factory()->create([
        'cupping_avg' => 85,
        'fragrance_avg' => 8,
        'evaluation_count' => 10,
        'verification_status' => 'verified',
    ]);

    $second = Offering::factory()->create([
        'cupping_avg' => 82,
        'fragrance_avg' => 7,
        'evaluation_count' => 8,
        'verification_status' => 'verified',
    ]);

    Offering::factory()->create([
        'cupping_avg' => 60,
        'fragrance_avg' => 4,
        'evaluation_count' => 2,
    ]);

    $response = $this->getJson('/offerings?' . http_build_query([
        'cuppingAvgMin' => 80,
        'cuppingAvgMax' => 90,
        'fragranceMin' => 6,
        'evaluationCountMin' => 5,
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $ulids = collect($response->json('data'))->pluck('ulid');
    expect($ulids)->toContain($match->ulid, $second->ulid);

    $locations = collect($response->json('data'))->pluck('location.ulid')->unique();
    expect($locations->count())->toBeGreaterThan(1);
});

test('filter_offerings_by_cata_ref', function () {
    [$owner] = authenticate('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);


    $taxonomy = getOlfactoryTaxonomyCollection('aromatics', false, 1)->first();

    $match = Offering::factory()->create();
    $match->offeringTastes()->create([
        'taxonomy_ref' => $taxonomy->id,
        'type' => 'fragrance',
        'level' => (string) $taxonomy->level,
        'parent_id' => null,
        'count' => 3,
    ]);

    Offering::factory()->create();

    $response = $this->getJson('/offerings?' . http_build_query([
        'cataRef' => [$taxonomy->ulid],
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $match->ulid);
});
