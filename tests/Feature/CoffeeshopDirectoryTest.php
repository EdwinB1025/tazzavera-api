<?php

use App\Models\CoffeeInventory;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Offering;
use App\Models\User;
use Database\Seeders\CreateOfferingSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(
    function () {
        $this->seed(RolesSeeder::class);
    }
);

/** A coffee shop (business) with the given locations, each with its primary contact */
function createCoffeeshop(string $name, array $locations = [[]]): User
{
    $user = User::factory()->assignRole('coffeeshop')->create(['name' => $name, 'surname' => 'Owner-Surname']);

    foreach ($locations as $contact) {
        $location = Location::factory()->create(['user_id' => $user->id]);
        updateContact([
            'is_primary' => true,
            'city' => 'Barcelona',
            'postal_code' => '08001',
            ...$contact,
        ], $location);
    }

    return $user;
}

/** An offering in the given location, with the given verification status */
function createOfferingAt(Location $location, string $status = 'provisional'): Offering
{
    $inventory = CoffeeInventory::whereNotIn('id', $location->offerings()->pluck('coffee_inventory_id'))->firstOrFail();

    return Offering::factory()->create([
        'location_id' => $location->id,
        'coffee_inventory_id' => $inventory->id,
        'verification_status' => $status,
    ]);
}

test('anyone_lists_coffeeshops_without_token', function () {
    createCoffeeshop('Nomad Coffee', [[], []]);

    $this->getJson('/coffeeshops')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Nomad Coffee')
        ->assertJsonPath('data.0.locationsCount', 2)
        ->assertJsonCount(2, 'data.0.locations')
        ->assertJsonStructure([
            'data' => ['*' => [
                'ulid',
                'name',
                'locationsCount',
                'offeringsCount',
                'verifiedOfferingsCount',
                'locations' => ['*' => ['ulid', 'name', 'description', 'latitud', 'longitud', 'address', 'city', 'postalCode', 'country', 'phone', 'web', 'social']],
            ]],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total'],
        ]);
});

test('coffeeshops_list_only_coffeeshop_users_with_locations', function () {
    createCoffeeshop('Nomad Coffee');
    User::factory()->assignRole('coffeeshop')->create(['name' => 'No Locations Coffee']);
    $specialist = User::factory()->assignRole('specialist')->create(['name' => 'Specialist Coffee']);
    Location::factory()->create(['user_id' => $specialist->id]);

    $this->getJson('/coffeeshops')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Nomad Coffee');
});

test('filter_coffeeshops_by_name', function (string $name, array $expected) {
    createCoffeeshop('Nomad Coffee');
    createCoffeeshop("Satan's Coffee Corner");
    createCoffeeshop('Syra Coffee');

    $response = $this->getJson('/coffeeshops?' . http_build_query(['name' => $name]))->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toBe($expected);
})->with([
    'partial'          => ['coffee', ['Nomad Coffee', "Satan's Coffee Corner", 'Syra Coffee']],
    'case insensitive' => ['NOMAD', ['Nomad Coffee']],
    'no match'         => ['Cometa', []],
]);

test('filter_coffeeshops_by_city_and_postal_code', function (array $filters, array $expected) {
    createCoffeeshop('Nomad Coffee', [['postal_code' => '08001'], ['postal_code' => '08003']]);
    createCoffeeshop('Syra Coffee', [['postal_code' => '08012']]);
    createCoffeeshop('Girona Coffee', [['city' => 'Girona', 'postal_code' => '17001']]);

    $response = $this->getJson('/coffeeshops?' . http_build_query($filters))->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toBe($expected);
})->with([
    'city'                  => [['city' => 'Barcelona'], ['Nomad Coffee', 'Syra Coffee']],
    'other city'            => [['city' => 'Girona'], ['Girona Coffee']],
    'postal code any location' => [['postalCode' => '08003'], ['Nomad Coffee']],
    'city + postal code'    => [['city' => 'Barcelona', 'postalCode' => '08012'], ['Syra Coffee']],
]);

test('filter_coffeeshops_by_city_ignores_owner_personal_contact', function () {
    $coffeeshop = createCoffeeshop('Nomad Coffee', [['city' => 'Barcelona']]);
    $coffeeshop->contacts()->update(['city' => 'Madrid']);

    $this->getJson('/coffeeshops?city=Madrid')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('filter_coffeeshops_by_verified_offerings', function (?string $verified, array $expected) {
    $this->seed(CreateOfferingSeeder::class);

    $verifiedShop = createCoffeeshop('Nomad Coffee', [[], []]);
    $provisionalShop = createCoffeeshop('Syra Coffee');
    createCoffeeshop('Cometa');

    createOfferingAt($verifiedShop->locations()->latest('id')->first(), 'verified');
    createOfferingAt($verifiedShop->locations()->oldest('id')->first());
    createOfferingAt($provisionalShop->locations()->first());

    $query = $verified === null ? '' : "?verified={$verified}";
    $response = $this->getJson("/coffeeshops{$query}")->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toBe($expected);
})->with([
    'verified=1' => ['1', ['Nomad Coffee']],
    'verified=0' => ['0', ['Cometa', 'Syra Coffee']],
    'omitted'    => [null, ['Cometa', 'Nomad Coffee', 'Syra Coffee']],
]);

test('coffeeshop_counters', function () {
    $this->seed(CreateOfferingSeeder::class);

    $coffeeshop = createCoffeeshop('Nomad Coffee', [[], []]);
    [$first, $second] = $coffeeshop->locations()->orderBy('id')->get()->all();
    createOfferingAt($first, 'verified');
    createOfferingAt($first);
    createOfferingAt($second, 'verified');

    $this->getJson("/coffeeshops/{$coffeeshop->ulid}")
        ->assertOk()
        ->assertJsonPath('data.locationsCount', 2)
        ->assertJsonPath('data.offeringsCount', 3)
        ->assertJsonPath('data.verifiedOfferingsCount', 2);
});

test('order_coffeeshops', function (array $query, array $expected) {
    createCoffeeshop('Syra Coffee');
    createCoffeeshop('Cometa');
    createCoffeeshop('Nomad Coffee');

    $response = $this->getJson('/coffeeshops?' . http_build_query($query))->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toBe($expected);
})->with([
    'default name asc' => [[], ['Cometa', 'Nomad Coffee', 'Syra Coffee']],
    'name desc'        => [['orderBy' => 'name', 'orderDirection' => 'desc'], ['Syra Coffee', 'Nomad Coffee', 'Cometa']],
    'desc only'        => [['orderDirection' => 'desc'], ['Syra Coffee', 'Nomad Coffee', 'Cometa']],
]);

test('paginate_coffeeshops', function () {
    foreach (range(1, 17) as $n) {
        createCoffeeshop(sprintf('Coffee %02d', $n));
    }

    $this->getJson('/coffeeshops?page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Coffee 16')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.total', 17);
});

test('filter_coffeeshops_with_invalid_parameters', function (array $query, array $errors) {
    $this->getJson('/coffeeshops?' . http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'order by unknown field' => [['orderBy' => 'email'], ['orderBy']],
    'order direction'        => [['orderDirection' => 'up'], ['orderDirection']],
    'verified not boolean'   => [['verified' => 'yes'], ['verified']],
    'page zero'              => [['page' => 0], ['page']],
]);

test('anyone_gets_a_coffeeshop', function () {
    $coffeeshop = createCoffeeshop('Nomad Coffee', [['address' => 'Carrer de la Riereta 1', 'postal_code' => '08001']]);

    $this->getJson("/coffeeshops/{$coffeeshop->ulid}")
        ->assertOk()
        ->assertJsonPath('data.ulid', $coffeeshop->ulid)
        ->assertJsonPath('data.name', 'Nomad Coffee')
        ->assertJsonPath('data.locations.0.address', 'Carrer de la Riereta 1')
        ->assertJsonPath('data.locations.0.postalCode', '08001');
});

test('get_a_coffeeshop_not_found', function (string $case) {
    $ulid = match ($case) {
        'unknown' => (string) \Illuminate\Support\Str::ulid(),
        'not a coffeeshop' => tap(User::factory()->assignRole('specialist')->create(), fn($u) => Location::factory()->create(['user_id' => $u->id]))->ulid,
    };

    $this->getJson("/coffeeshops/{$ulid}")->assertNotFound();
})->with(['unknown', 'not a coffeeshop']);

test('coffeeshop_offerings_are_read_with_the_offerings_filter', function () {
    $this->seed(CreateOfferingSeeder::class);

    $coffeeshop = createCoffeeshop('Nomad Coffee');
    $offering = createOfferingAt($coffeeshop->locations()->first());
    $other = createCoffeeshop('Syra Coffee');
    createOfferingAt($other->locations()->first());

    $this->getJson("/offerings?coffeeshopUlid={$coffeeshop->ulid}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $offering->ulid);
});

test('coffeeshop_responses_expose_no_personal_data', function () {
    $coffeeshop = createCoffeeshop('Nomad Coffee', [['email' => 'location@nomad.example']]);
    $personal = $coffeeshop->contacts()->first();
    $personal->update(['email' => 'owner.personal@example.com', 'phone' => '+34 600 000 999', 'address' => 'Private Street 99']);

    foreach (["/coffeeshops", "/coffeeshops/{$coffeeshop->ulid}"] as $uri) {
        $body = $this->getJson($uri)->assertOk()->getContent();

        expect($body)
            ->not->toContain($coffeeshop->email)
            ->not->toContain('Owner-Surname')
            ->not->toContain('owner.personal@example.com')
            ->not->toContain('+34 600 000 999')
            ->not->toContain('Private Street 99')
            ->not->toContain($personal->ulid)
            ->not->toContain('location@nomad.example')
            ->not->toContain('"email"')
            ->not->toContain('"surname"')
            ->not->toContain('"role"');
    }
});
