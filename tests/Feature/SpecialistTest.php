<?php

use App\Models\OlfactoryTaxonomy;
use Database\Seeders\CertificationTypeSeeder;
use Database\Seeders\GetAnOfferingSeeder;
use Database\Seeders\OlfactoryTaxonomySeeder;
use Database\Seeders\RolesSeeder;

beforeEach(
    function () {
        $this->seed(RolesSeeder::class);
        $this->seed(CertificationTypeSeeder::class);
        $this->seed(OlfactoryTaxonomySeeder::class);
        $this->seed(GetAnOfferingSeeder::class);
    }
);

test('specialist_create_evaluation', function () {
    [$user, $token] = authenticate('specialist');

    $offering = createOfferingsForUser($user, 1, 1);

    $cataFragance = getOlfactoryTaxonomyCollection('aromatics', false, 2)
        ->pluck('ulid')
        ->toArray();

    $cataAroma = getOlfactoryTaxonomyCollection('aromatics', false, 1)
        ->pluck('ulid')
        ->toArray();;

    $cataFlavor = getOlfactoryTaxonomyCollection('aromatics', false, 1)
        ->pluck('ulid')
        ->toArray();;

    $cataAftertaste = getOlfactoryTaxonomyCollection('aromatics', false, 2)
        ->pluck('ulid')
        ->toArray();;

    $cataMouthfeel = getOlfactoryTaxonomyCollection('mouthfeel', false, 1)
        ->pluck('ulid')
        ->toArray();;

    $cataMainTastes = getOlfactoryTaxonomyCollection('main_tastes', false, 1)
        ->pluck('ulid')
        ->toArray();;

    $cataDefects = getOlfactoryTaxonomyCollection('defects', false, 1)
        ->pluck('ulid')
        ->toArray();;


    $payLoad = [
        'offeringId' => $offering->ulid,
        'extractionMethod' => 'v60',
        'descriptive' => [
            'roastLevel' => 'medium',
            'fragrance'  => ['score' => 8,  'cata' => $cataFragance, 'note' => null],
            'aroma'      => ['score' => 9,  'cata' => $cataAroma, 'note' => null],
            'flavor'     => ['score' => 11, 'cata' => $cataFlavor, 'note' => null],
            'aftertaste' => ['score' => 10, 'cata' => $cataAftertaste, 'note' => null],
            'acidity'    => ['score' => 9,  'note' => null],
            'sweetness'  => ['score' => 10, 'note' => null],
            'mouthfeel'  => ['score' => 8,  'cata' => $cataMouthfeel, 'note' => null],
            'mainTastes' => $cataMainTastes,
        ],
        'affective' => [
            'fragrance'  => ['score' => 7, 'note' => null],
            'aroma'      => ['score' => 8, 'note' => null],
            'flavor'     => ['score' => 7, 'note' => null],
            'aftertaste' => ['score' => 8, 'note' => null],
            'acidity'    => ['score' => 7, 'note' => null],
            'sweetness'  => ['score' => 8, 'note' => null],
            'mouthfeel'  => ['score' => 6, 'note' => null],
            'overall'    => ['score' => 8, 'note' => 'Balanced, clean finish.'],
            'defects'    => $cataDefects,
        ],
        'extrinsics' => [
            'farming' => null,
            'processing' => null,
            'trading' => null,
            'certifications' => null,
            'generalObservation' => null,
        ],
    ];

    $this->withToken($token)
        ->postJson('/evaluations', $payLoad)
        ->dump()
        ->assertJsonPath('data.offeringId', $offering->ulid)
        ->assertStatus(201);
});
