<?php

use Database\Seeders\OlfactoryTaxonomySeeder;

beforeEach(
    function () {
        $this->seed(OlfactoryTaxonomySeeder::class);
    }
);

test('public_retrieves_taxonomy_tree', function () {


    $structure = [
        'data' => [
            '*' => [
                'level',
                'nameEn',
                'nameEs',
                'descriptionEn',
                'descriptionEs',
                'color',
                'categories',
                'children' => [
                    '*' => [
                        'level',
                        'nameEn',
                        'nameEs',
                        'children',
                    ],
                ],
            ],
        ],
    ];


    $this->getJson('/taxonomies')
        //->dump()
        ->assertOk()
        ->assertJsonStructure($structure);
});

test('public_retrieves_taxonomy_tree_in_catalan', function () {

    $nodes = collect($this->getJson('/taxonomies')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'nameCa',
                    'descriptionCa',
                    'children' => [
                        '*' => [
                            'nameCa',
                            'descriptionCa',
                            'children',
                        ],
                    ],
                ],
            ],
        ])
        ->json('data'));

    /** Flattening the three levels of the tree */
    $flatten = function ($nodes) use (&$flatten) {
        return collect($nodes)->flatMap(fn($node) => [$node, ...$flatten($node['children'] ?? [])]);
    };
    $all = $flatten($nodes);

    expect($all)->toHaveCount(120);
    expect($all->pluck('level')->unique()->sort()->values()->all())->toBe([0, 1, 2]);

    foreach ($all as $node) {
        expect($node['nameCa'])->not->toBeNull();
        expect($node['descriptionCa'])->not->toBeNull();
    }
});
