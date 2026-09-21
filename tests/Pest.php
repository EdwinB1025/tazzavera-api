<?php

use App\Models\Coffee;
use App\Models\CoffeeInventory;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Offering;
use App\Models\OlfactoryTaxonomy;
use App\Models\Roastery;
use App\Models\User;
use Database\Seeders\GetAnOfferingSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection as SupportCollection;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function authenticate($role = 'user'): array
{

    // Create User
    $password = 'testFakeUser1234';
    $user = User::factory()->assignRole($role)->create(['password' => $password]);

    // Create a client
    $client = app(ClientRepository::class)
        ->createPasswordGrantClient('Test Password Client', 'users', false);

    $response = test()->post('/oauth/token', [
        'grant_type' => 'password',
        'client_id' => $client->id,
        'username' => $user->email,
        'password' => $password,
    ]);

    $token = $response->json('access_token');

    return [$user, $token, $password, $response];
}

function authenticateWithWriteScope($role = 'user'): array
{

    // Create User
    $password = 'testFakeUser1234';
    $user = User::factory()->assignRole($role)->create(['password' => $password]);

    // Create a client
    $client = app(ClientRepository::class)
        ->createPasswordGrantClient('Test Password Client', 'users', false);

    $response = test()->post('/oauth/token', [
        'grant_type' => 'password',
        'client_id' => $client->id,
        'username' => $user->email,
        'password' => $password,
        'scope' => 'profile:write'
    ]);

    $token = $response->json('access_token');

    return [$user, $token, $password, $response];
}

function createCoffee(array $raw = [], int $count = 1): Coffee|Collection
{
    $coffee = Coffee::factory()->count($count)->create($raw);
    return $count > 1 ? $coffee : $coffee->first();
}

function createRoastery(array $raw = [], array $contact = [], int $count = 1): Roastery|Collection
{

    $result = $count > 1 ?
        Roastery::factory()->count($count)->create($raw) : Roastery::factory()->create($raw);

    updateContact($contact, $result);

    return $result;
}

function createLocation(array $raw = [], array $contact = [], int $count = 1): Location|Collection
{

    $result = $count > 1 ?
        Location::factory()->count($count)->create($raw) : Location::factory()->create($raw);

    updateContact($contact, $result);

    return $result;
}

function updateContact(array $contact, Model|Collection $model): void
{
    if ($model instanceof Model && $contact) {
        $contactData = Contact::factory()->make($contact)->only([
            'is_primary',
            'phone',
            'email',
            'web',
            'social',
            'address',
            'country',
            'city',
            'postal_code',
        ]);

        $model->contacts()->updateOrCreate(
            ['is_primary' => true],
            $contactData
        );
    }
}

function createOfferingsForUser(User $user, int $count, int $numLocations): SupportCollection|Offering
{
    $inventories = CoffeeInventory::inRandomOrder()->take($count)->get();

    if ($inventories->count() < $count) {
        test()->seed(GetAnOfferingSeeder::class);
        $inventories = CoffeeInventory::inRandomOrder()->take($count)->get();
    }

    $locations = Location::factory()->count($numLocations)->create([
        'user_id' => $user->id,
    ]);

    $offerings = $locations->flatMap(
        fn($location) =>
        $inventories->map(
            fn($inventory) =>
            Offering::factory()->create([
                'location_id' => $location->id,
                'coffee_inventory_id' => $inventory->id,
            ])
        )
    );


    return $offerings->count() === 1 ? $offerings->first() : $offerings;
}

function getOlfactoryTaxonomyCollection($category = 'aromatics', $all = false, $count = 1)
{

    switch ($category) {
        case 'mouthfeel':
            $result = OlfactoryTaxonomy::where('level', 1)
                ->whereJsonContains('categories', $category);
            $result = $all ? $result->get() : $result->inRandomOrder()->limit($count)->get();
            break;

        case 'main_tastes':
            $result = OlfactoryTaxonomy::where('level', 0)
                ->whereJsonContains('categories', $category);
            $result = $all ? $result->get() : $result->inRandomOrder()->limit($count)->get();
            break;

        default:

            /**Retrieve all the bottom models */
            $orphan = OlfactoryTaxonomy::doesntHave('children')
                ->whereJsonContains('categories', $category);

            $result = $all ? $orphan->get() : $orphan->inRandomOrder()->limit($count)->get();

            /**EDB 09/18/26: it will be responsability of fron to print the olfactory taxonomy, keeping code for a future use
            $branches->each->setAttribute('isBottom', true);

            if (! $all) {
                $branches = $branches->take($count);
            }

            /Filter grandchild with relationship to add relationships

            $child = $branches->map->parent->filter();
            $parent = $child->map->parent->filter();
            $result = $branches->concat($child)->concat($parent)->unique('id')->values();
             */
            break;
    }

    return $result;
}

function createEvaluationPayload(
    Offering $offering,
    $minScore = 7,
    $extractionMethod = 'v60',
    $fraganceCount = 2,
    $aromaCount = 1,
    $flavorCount = 1,
    $afterTasteCount = 1,
    $mouthfeelCount = 1,
    $mainTastesCount = 1,
    $defectsCount = 1,
    $notes = ['affective.overall' => 'Balanced, clean finish.'],
): array {
    $cataFragance = getOlfactoryTaxonomyCollection('aromatics', false, $fraganceCount)
        ->pluck('ulid')
        ->toArray();

    $cataAroma = getOlfactoryTaxonomyCollection('aromatics', false, $aromaCount)
        ->pluck('ulid')
        ->toArray();

    $cataFlavor = getOlfactoryTaxonomyCollection('aromatics', false, $flavorCount)
        ->pluck('ulid')
        ->toArray();

    $cataAftertaste = getOlfactoryTaxonomyCollection('aromatics', false, $afterTasteCount)
        ->pluck('ulid')
        ->toArray();

    $cataMouthfeel = getOlfactoryTaxonomyCollection('mouthfeel', false, $mouthfeelCount)
        ->pluck('ulid')
        ->toArray();

    $cataMainTastes = getOlfactoryTaxonomyCollection('main_tastes', false, $mainTastesCount)
        ->pluck('ulid')
        ->toArray();

    $cataDefects = getOlfactoryTaxonomyCollection('defects', false, $defectsCount)
        ->pluck('ulid')
        ->toArray();


    $payLoad = [
        'offeringId' => $offering->ulid,
        'extractionMethod' => $extractionMethod,
        'descriptive' => [
            'roastLevel' => 'medium',
            'fragrance'  => ['score' => fake()->numberBetween(1, 15),  'cata' => $cataFragance, 'note' => $notes['descriptive.fragrance'] ?? null],
            'aroma'      => ['score' => fake()->numberBetween(1, 15),  'cata' => $cataAroma, 'note' => $notes['descriptive.aroma'] ?? null],
            'flavor'     => ['score' => fake()->numberBetween(1, 15), 'cata' => $cataFlavor, 'note' => $notes['descriptive.flavor'] ?? null],
            'aftertaste' => ['score' => fake()->numberBetween(1, 15), 'cata' => $cataAftertaste, 'note' => $notes['descriptive.aftertaste'] ?? null],
            'acidity'    => ['score' => fake()->numberBetween(1, 15),  'note' => $notes['descriptive.acidity'] ?? null],
            'sweetness'  => ['score' => fake()->numberBetween(1, 15), 'note' => $notes['descriptive.sweetness'] ?? null],
            'mouthfeel'  => ['score' => fake()->numberBetween(1, 15),  'cata' => $cataMouthfeel, 'note' => $notes['descriptive.mouthfeel'] ?? null],
            'mainTastes' => $cataMainTastes,
        ],
        'affective' => [
            'fragrance'  => ['score' => fake()->numberBetween($minScore, 9), 'note' => $notes['affective.fragrance'] ?? null],
            'aroma'      => ['score' => fake()->numberBetween($minScore, 9), 'note' => $notes['affective.aroma'] ?? null],
            'flavor'     => ['score' => fake()->numberBetween($minScore, 9), 'note' => $notes['affective.flavor'] ?? null],
            'aftertaste' => ['score' => fake()->numberBetween($minScore, 9), 'note' => $notes['affective.aftertaste'] ?? null],
            'acidity'    => ['score' => fake()->numberBetween($minScore, 9), 'note' => $notes['affective.acidity'] ?? null],
            'sweetness'  => ['score' => fake()->numberBetween($minScore, 9), 'note' => $notes['affective.sweetness'] ?? null],
            'mouthfeel'  => ['score' => fake()->numberBetween($minScore, 9), 'note' => $notes['affective.mouthfeel'] ?? null],
            'overall'    => ['score' => fake()->numberBetween($minScore, 9), 'note' => $notes['affective.overall'] ?? null],
            'defects'    => $cataDefects,
        ],
        'extrinsics' => [
            'farming' => $notes['extrinsics.farming'] ?? null,
            'processing' => $notes['extrinsics.processing'] ?? null,
            'trading' => $notes['extrinsics.trading'] ?? null,
            'certifications' => $notes['extrinsics.certifications'] ?? null,
            'generalObservation' => $notes['extrinsics.generalObservation'] ?? null,
        ],
    ];

    return $payLoad;
}
