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

function createOfferingsForUser(User $user, int $count, int $numLocations): Collection|Offering
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
            $result = $all ? $result->get() : $result->limit($count)->get();
            break;

        case 'main_tastes':
            $result = OlfactoryTaxonomy::where('level', 0)
                ->whereJsonContains('categories', $category);
            $result = $all ? $result->get() : $result->limit($count)->get();
            break;

        default:

            /**Retrieve all the bottom models */
            $orphan = OlfactoryTaxonomy::doesntHave('children')
                ->whereJsonContains('categories', $category)
                ->get();

            $result = $orphan->concat($orphan)->unique('id')->values();

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
