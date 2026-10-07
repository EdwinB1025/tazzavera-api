<?php

use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolesSeeder;

beforeEach(
    function () {
        $this->seed(RolesSeeder::class);
    }
);

function contactPayload(array $overrides = []): array
{
    return [
        'phone' => '+34 932 123 456',
        'email' => 'hola@cafeteria.example',
        'web' => 'https://cafeteria.example',
        'social' => '@cafeteria.bcn',
        'address' => 'Carrer de Verdi 12',
        'country' => 'España',
        'city' => 'Barcelona',
        'postal_code' => '08012',
        ...$overrides,
    ];
}

test('guest_cannot_read_or_create_contacts', function () {
    $user = User::factory()->create();

    $this->getJson("/users/{$user->ulid}/contacts")->assertUnauthorized();
    $this->postJson("/users/{$user->ulid}/contacts", contactPayload())->assertUnauthorized();
});

test('authenticated_user_lists_own_contacts', function () {
    [$user, $token] = authenticate();

    $this->withToken($token)
        ->getJson("/users/{$user->ulid}/contacts")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.isPrimary', true)
        ->assertJsonStructure(['data' => ['*' => ['ulid', 'isPrimary', 'phone', 'email', 'web', 'social', 'address', 'country', 'city', 'postalCode']]]);
});

test('authenticated_user_cannot_list_other_user_contacts', function () {
    [, $token] = authenticate();
    $other = User::factory()->create();

    $this->withToken($token)
        ->getJson("/users/{$other->ulid}/contacts")
        ->assertForbidden();
});

test('authenticated_user_creates_primary_contact', function () {
    [$user, $token] = authenticateWithWriteScope();
    $user->contacts()->delete();

    $this->withToken($token)
        ->postJson("/users/{$user->ulid}/contacts", contactPayload(['is_primary' => false]))
        ->assertCreated()
        ->assertJsonPath('data.isPrimary', true)
        ->assertJsonPath('data.city', 'Barcelona')
        ->assertJsonPath('data.postalCode', '08012')
        ->assertJsonPath('message', __('contacts.created'));

    expect($user->contacts()->where('is_primary', true)->count())->toBe(1);
});

test('authenticated_user_cannot_create_second_primary_contact', function () {
    [$user, $token] = authenticateWithWriteScope();

    $this->withToken($token)
        ->postJson("/users/{$user->ulid}/contacts", contactPayload())
        ->assertStatus(409)
        ->assertJsonPath('message', __('contacts.primary_exists'));

    expect($user->contacts()->count())->toBe(1);
});

test('second_primary_contact_message_is_translated', function () {
    [$user, $token] = authenticateWithWriteScope();

    $this->withToken($token)
        ->withHeader('Accept-Language', 'es')
        ->postJson("/users/{$user->ulid}/contacts", contactPayload())
        ->assertStatus(409)
        ->assertJsonPath('message', 'El usuario ya tiene un contacto principal.');
});

test('authenticated_user_cannot_create_contact_for_other_user', function () {
    [, $token] = authenticateWithWriteScope();
    $other = User::factory()->create();
    $other->contacts()->delete();

    $this->withToken($token)
        ->postJson("/users/{$other->ulid}/contacts", contactPayload())
        ->assertForbidden();

    expect($other->contacts()->count())->toBe(0);
});

test('authenticated_user_cannot_create_contact_without_write_scope', function () {
    [$user, $token] = authenticate();
    $user->contacts()->delete();

    $this->withToken($token)
        ->postJson("/users/{$user->ulid}/contacts", contactPayload())
        ->assertForbidden();
});

test('authenticated_user_creates_contact_with_invalid_data', function (array $payload, array $errors) {
    [$user, $token] = authenticateWithWriteScope();
    $user->contacts()->delete();

    $this->withToken($token)
        ->postJson("/users/{$user->ulid}/contacts", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);

    expect(Contact::where('contactable_id', $user->id)->count())->toBe(0);
})->with([
    'missing address and city' => [contactPayload(['address' => null, 'city' => null]), ['address', 'city']],
    'invalid email'            => [contactPayload(['email' => 'not-an-email']), ['email']],
    'invalid web'              => [contactPayload(['web' => 'not a url']), ['web']],
    'too long phone'           => [contactPayload(['phone' => str_repeat('9', 26)]), ['phone']],
    'too long postal code'     => [contactPayload(['postal_code' => str_repeat('0', 13)]), ['postal_code']],
]);
