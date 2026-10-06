<?php

use App\Models\Contact;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolesSeeder;

beforeEach(
    function () {
        $this->seed(RolesSeeder::class);
    }
);

function locationPayload(array $overrides = [], array $contact = []): array
{
    return [
        'name' => 'Cafeteria Gràcia',
        'description' => 'Barra de especialidad en Gràcia.',
        'latitud' => 41.4029,
        'longitud' => 2.1565,
        'contact' => [
            'phone' => '+34 932 123 456',
            'address' => 'Carrer de Verdi 12',
            'country' => 'España',
            'city' => 'Barcelona',
            'postal_code' => '08012',
            ...$contact,
        ],
        ...$overrides,
    ];
}

test('guest_cannot_read_or_create_locations', function () {
    $user = User::factory()->assignRole('coffeeshop')->create();

    $this->getJson("/users/{$user->ulid}/locations")->assertUnauthorized();
    $this->postJson('/locations', locationPayload())->assertUnauthorized();
});

test('authenticated_user_lists_own_locations', function () {
    [$user, $token] = authenticate('coffeeshop');
    createLocation(['user_id' => $user->id], [], 2);
    createLocation();

    $this->withToken($token)
        ->getJson("/users/{$user->ulid}/locations")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => ['*' => ['ulid', 'name', 'description', 'latitud', 'longitud', 'contacts' => ['*' => ['ulid', 'isPrimary', 'address', 'city', 'postalCode']]]]]);
});

test('authenticated_user_cannot_list_other_user_locations', function () {
    [, $token] = authenticate('coffeeshop');
    $other = createLocation()->user;

    $this->withToken($token)
        ->getJson("/users/{$other->ulid}/locations")
        ->assertForbidden();
});

test('coffeeshop_creates_location_with_coordinates', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');
    $other = User::factory()->create();

    $response = $this->withToken($token)
        ->postJson('/locations', locationPayload(['user_id' => $other->id]))
        ->assertCreated()
        ->assertJsonPath('data.name', 'Cafeteria Gràcia')
        ->assertJsonPath('data.latitud', '41.40290000')
        ->assertJsonPath('data.longitud', '2.15650000')
        ->assertJsonPath('data.contacts.0.isPrimary', true)
        ->assertJsonPath('data.contacts.0.address', 'Carrer de Verdi 12')
        ->assertJsonPath('message', __('locations.created'));

    $location = Location::where('ulid', $response->json('data.ulid'))->firstOrFail();
    expect($location->user_id)->toBe($user->id)
        ->and($location->contacts()->where('is_primary', true)->count())->toBe(1);
});

test('coffeeshop_creates_location_without_coordinates', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');

    $this->withToken($token)
        ->postJson('/locations', locationPayload(['latitud' => null, 'longitud' => null, 'description' => null]))
        ->assertCreated()
        ->assertJsonPath('data.latitud', null)
        ->assertJsonPath('data.longitud', null)
        ->assertJsonPath('data.description', null);

    $payload = locationPayload();
    unset($payload['latitud'], $payload['longitud'], $payload['description']);

    $this->withToken($token)
        ->postJson('/locations', $payload)
        ->assertCreated();

    expect($user->locations()->count())->toBe(2);
});

test('non_coffeeshop_cannot_create_location', function () {
    [, $token] = authenticateWithWriteScope('specialist');

    $this->withToken($token)
        ->postJson('/locations', locationPayload())
        ->assertForbidden();

    expect(Location::count())->toBe(0);
});

test('coffeeshop_cannot_create_location_without_write_scope', function () {
    [, $token] = authenticate('coffeeshop');

    $this->withToken($token)
        ->postJson('/locations', locationPayload())
        ->assertForbidden();

    expect(Location::count())->toBe(0);
});

test('coffeeshop_creates_location_with_invalid_data', function (array $payload, array $errors) {
    [, $token] = authenticateWithWriteScope('coffeeshop');

    $this->withToken($token)
        ->postJson('/locations', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);

    expect(Location::count())->toBe(0);
})->with([
    'missing name'           => [locationPayload(['name' => null]), ['name']],
    'too long name'          => [locationPayload(['name' => str_repeat('a', 151)]), ['name']],
    'latitud out of range'   => [locationPayload(['latitud' => 90.5]), ['latitud']],
    'longitud out of range'  => [locationPayload(['longitud' => -180.5]), ['longitud']],
    'non numeric latitud'    => [locationPayload(['latitud' => 'north']), ['latitud']],
    'only latitud'           => [locationPayload(['longitud' => null]), ['longitud']],
    'only longitud'          => [locationPayload(['latitud' => null]), ['latitud']],
    'missing contact'        => [locationPayload(['contact' => null]), ['contact']],
    'contact without address and city' => [locationPayload([], ['address' => null, 'city' => null]), ['contact.address', 'contact.city']],
    'contact invalid email'  => [locationPayload([], ['email' => 'not-an-email']), ['contact.email']],
]);

test('location_creation_rolls_back_when_contact_fails', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');

    Contact::creating(function () {
        throw new RuntimeException('Contact could not be stored.');
    });

    $this->withToken($token)
        ->postJson('/locations', locationPayload())
        ->assertServerError();

    expect(Location::count())->toBe(0)
        ->and($user->locations()->count())->toBe(0);
});
