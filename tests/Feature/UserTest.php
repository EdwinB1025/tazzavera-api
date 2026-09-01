<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

test('create_new_user', function (?string $role = 'user') {
    $user = User::factory()->registrationPayload($role);
    $response = $this->postJson('/register', $user);
    $response->dump();
    $response->assertStatus(201);
})->with(['user', 'coffeeshop', 'specialist']);

test('autenticate_user', function () {

    $user = User::factory()->create();
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'PKCE Test',
        ['http://localhost/callback'],
        false
    );

    $this->actingAs($user, 'web');
    $codeVerifier = Str::random(128);
    $state = Str::random(48);

    /**Generating code challenge as described in the specification */
    $encoded = base64_encode(hash('sha256', $codeVerifier, true));
    $codeChallenge = strtr(rtrim($encoded, '='), '+/', '-_');


    /** Creating the initial authorization request */

    $query = http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost/callback',
        'response_type' => 'code',
        'scope' => '*',
        'state' => $state,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
    ]);

    $codeRequest = $this->get('oauth/authorize?' . $query);

    $found = app(\Laravel\Passport\ClientRepository::class)->find($client->id);
    dump(get_class($found));
    dump($found->skipsAuthorization($user, []));

    $codeRequest->assertStatus(302);

    $codeRequest->dumpHeaders();
    $codeRequest->dump();

    /** Retrieven the authentication code */

    $location = $codeRequest->headers->get('Location');
    parse_str(parse_url($location, PHP_URL_QUERY), $params);
    $code = $params['code'];
    $returnedState = $params['state'];

    if ($code && $returnedState === $state) {
        $tokenResponse = $this->post('oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'redirect_uri' => 'http://localhost/callback',
            'code' => $code,
            'code_verifier' => $codeVerifier,
        ]);

        $tokenResponse->dump();
        $tokenResponse->assertOk();
    } else {
        test()->fail('CodeVerifiers could not be retreived from Location attribute in the header, or state is different');
    }
});

test('logout_user', function () {
    [$user, $tokenResponse] = authenticateUser();

    //Validate token generation
    $tokenResponse->assertOk();
    $token = $tokenResponse->json('access_token');

    //Validate logout route
    $response = $this->withToken($token)->post('/logout');
    $response->assertOk();

    //Assert Token is revoked
    $this->assertDatabaseHas('oauth_access_tokens', [
        'user_id' => $user->id,
        'revoked' => true,
    ]);
});

test('user_updates_field', function (string $field, string $value) {

    [$user, $token] = authenticateUser();

    //Updating field using the api route

    $this->withToken($token)
        ->putJson("/users/{$user->id}", [$field => $value])
        ->assertOk();

    //asserting the field value in DB

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        $field => $value,
    ]);
})->with([
    'name'    => ['name', 'Nuevo Nombre'],
    'surname' => ['surname', 'Nuevo Apellido'],
    'email'   => ['email', 'nuevo@example.com'],
]);

test('user_updates_password', function () {

    [$user, $token, $password] = authenticateUser();

    //Updating password using the specific api route
    $this->withToken($token)
        ->putJson("/users/{$user->id}/password", [
            'current_password' => $password,
            'password' => 'nuevaClave1234',
            'password_confirmation' => 'nuevaClave1234',
        ])
        ->assertOk();

    // asserting the the password has been updated and hashed
    expect(Hash::check('nuevaClave1234', $user->fresh()->password))->toBeTrue();
});
