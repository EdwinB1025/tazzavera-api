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
                'name_en',
                'name_es',
                'description_en',
                'description_es',
                'color',
                'categories',
                'children' => [
                    '*' => [
                        'level',
                        'name_en',
                        'name_es',
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
