<?php

use App\Models\CertificationType;
use App\Models\Coffee;
use App\Models\CoffeeInventory;
use App\Models\Contact;
use App\Models\Evaluation;
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

test('authenticated_coffeeshop_deletes_offering_with_evaluations', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');

    $offering = createOfferingsForUser($user, 1, 1);

    $baseline = Evaluation::factory()->create([
        'offering_id' => $offering->id,
        'evaluator_id' => $user->id,
        'evaluation_type' => 'baseline',
    ]);
    $specialist = Evaluation::factory()->create(['offering_id' => $offering->id]);

    $this->withToken($token)
        ->deleteJson("/offerings/{$offering->ulid}")
        ->assertOK();

    $this->assertDatabaseMissing('offerings', ['id' => $offering->id]);
    $this->assertDatabaseMissing('evaluations', ['id' => $baseline->id]);
    $this->assertDatabaseHas('evaluations', ['id' => $specialist->id, 'offering_id' => null]);
});

test('authenticated_coffeeshop_deletes_offerings_with_evaluations', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');

    $offerings = createOfferingsForUser($user, 2, 1);

    $baselines = $offerings->map(fn($offering) => Evaluation::factory()->create([
        'offering_id' => $offering->id,
        'evaluator_id' => $user->id,
        'evaluation_type' => 'baseline',
    ]));
    $specialists = $offerings->map(fn($offering) => Evaluation::factory()->create(['offering_id' => $offering->id]));

    $this->withToken($token)
        ->deleteJson("/offerings", ['offerings' => $offerings->pluck('ulid')->toArray()])
        ->assertOK();

    foreach ($offerings as $offering) {
        $this->assertDatabaseMissing('offerings', ['id' => $offering->id]);
    }
    foreach ($baselines as $baseline) {
        $this->assertDatabaseMissing('evaluations', ['id' => $baseline->id]);
    }
    foreach ($specialists as $specialist) {
        $this->assertDatabaseHas('evaluations', ['id' => $specialist->id, 'offering_id' => null]);
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
    //EDB 10/09/26: profile:write no longer required; only the account's own actions need it (Product owner)
    [$user, $token] = authenticate('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);

    $offering = createOfferingsForUser($user, 1, 1);

    $this->withToken($token)
        ->deleteJson("/offerings/{$offering->ulid}")
        ->assertOk();
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
        'location_id' => Location::factory()->create(['user_id' => $owner->id])->id,
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
        //->dump()
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $ulids = collect($response->json('data'))->pluck('ulid');
    expect($ulids)->toContain($match->ulid, $second->ulid);

    $locations = collect($response->json('data'))->pluck('location.ulid')->unique();
    //dump($locations);
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

test('filter_offerings_by_verification_status', function (?string $verified, array $expected) {
    [$owner] = authenticate('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);

    Offering::query()->delete();

    $offerings = [
        'verified'    => Offering::factory()->create(['verification_status' => 'verified']),
        'provisional' => Offering::factory()->create(['verification_status' => 'provisional']),
    ];

    $query = $verified === null ? '' : '?' . http_build_query(['verified' => $verified]);

    $response = $this->getJson("/offerings{$query}");

    $response
        ->assertOk()
        ->assertJsonCount(count($expected), 'data');

    $ulids = collect($response->json('data'))->pluck('ulid');
    foreach ($expected as $status) {
        expect($ulids)->toContain($offerings[$status]->ulid);
    }
})->with([
    'verified=1' => ['1', ['verified']],
    'verified=0' => ['0', ['provisional']],
    'absent'     => [null, ['verified', 'provisional']],
]);

test('filter_offerings_by_invalid_verification_status', function () {
    $this->getJson('/offerings?verified=yes')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('verified');
});

test('filter_offerings_by_roastery_name', function (string $roasteryName, int $expected) {
    [$owner] = authenticate('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);

    $roastery = createRoastery(['name' => 'Tostadores Zzyxx Norte']);
    $inventory = CoffeeInventory::factory()->create(['roastery_id' => $roastery->id]);
    $match = Offering::factory()->create(['coffee_inventory_id' => $inventory->id]);

    $response = $this->getJson('/offerings?' . http_build_query(['roasteryName' => $roasteryName]));

    $response
        ->assertOk()
        ->assertJsonCount($expected, 'data');

    if ($expected > 0) {
        $response->assertJsonPath('data.0.ulid', $match->ulid);
    }
})->with([
    'partial match' => ['zzyxx', 1],
    'no match'      => ['qwvbn', 0],
]);

test('filter_offerings_by_roastery_name_combined', function () {
    [$owner] = authenticate('coffeeshop');

    $this->seed(GetAnOfferingSeeder::class);

    $roastery = createRoastery(['name' => 'Tostadores Zzyxx Norte']);
    $inventory = CoffeeInventory::factory()->create(['roastery_id' => $roastery->id]);
    $match = Offering::factory()->create([
        'coffee_inventory_id' => $inventory->id,
        'verification_status' => 'verified',
    ]);
    Offering::factory()->create([
        'coffee_inventory_id' => $inventory->id,
        'verification_status' => 'provisional',
    ]);

    $otherInventory = CoffeeInventory::factory()->create(['roastery_id' => createRoastery(['name' => 'Otro Tostador'])->id]);
    Offering::factory()->create([
        'coffee_inventory_id' => $otherInventory->id,
        'verification_status' => 'verified',
    ]);

    $this->getJson('/offerings?' . http_build_query(['roasteryName' => 'zzyxx', 'verified' => 1]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $match->ulid);
});

test('filter_offerings_by_too_long_roastery_name', function () {
    $this->getJson('/offerings?roasteryName=' . str_repeat('a', 151))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('roasteryName');
});
