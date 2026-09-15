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
