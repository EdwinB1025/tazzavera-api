<?php

use App\Models\Coffee;
use App\Models\Location;
use App\Models\Offering;
use App\Models\OlfactoryTaxonomy;
use App\Models\Roastery;
use App\Models\User;
use Database\Seeders\FeaturedCoffeeSeeder;
use Database\Seeders\OlfactoryTaxonomySeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, OlfactoryTaxonomySeeder::class]);
    Roastery::factory()->create();
    Location::factory()->create(['user_id' => User::factory()->assignRole('coffeeshop')->create()->id]);
});

/** EDB 10/13/26: Enlu and Opita Café are seeded once, verified, with only the taxonomy notes they have */
test('featured_coffee_seeder_creates_the_verified_coffees_with_their_notes', function () {
    $this->seed(FeaturedCoffeeSeeder::class);
    $this->seed(FeaturedCoffeeSeeder::class);

    $notes = fn(string $name) => Offering::whereHas('coffeeInventory.coffee', fn($q) => $q->where('name', $name))
        ->firstOrFail()
        ->offeringTastes()
        ->where('type', 'aromatics')
        ->pluck('taxonomy_ref');
    $names = fn($refs) => OlfactoryTaxonomy::whereIn('id', $refs)->where('level', 2)->pluck('name_en')->sort()->values()->all();

    expect(Coffee::whereIn('name', ['Enlu', 'Opita Café'])->count())->toBe(2)
        ->and(Coffee::firstWhere('name', 'Enlu')->altitude)->toBe(1950)
        ->and(Offering::where('verification_status', 'verified')->count())->toBe(2)
        ->and($names($notes('Enlu')))->toBe(['Honey', 'Pineapple', 'Winey'])
        ->and($names($notes('Opita Café')))->toBe(['Caramelized', 'Peach']);
});

/** EDB 10/13/26: generated coffees are named like a label: region, variety and process */
test('coffee_factory_names_the_coffee_after_its_origin', function () {
    $coffee = Coffee::factory()->create(['region' => 'Huila', 'variety' => 'Caturra', 'process' => 'washed']);

    expect($coffee->name)->toBe('Huila Caturra Lavado');
});
