<?php

use App\Http\Controllers\UserController;
use App\Models\Passport\Client;
use App\Models\User;
use App\Services\RegistrationTokenService;
use Database\Seeders\FrontClientSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

test('user_registers', function (?string $role = 'user') {
    $user = User::factory()->registrationPayload($role);
    $response = $this->postJson('/register', $user);
    //$response->dump();
    $response->assertStatus(201);
})->with(['user', 'coffeeshop', 'specialist']);

test('user_registers_and_is_signed_in', function () {
    $this->seed(FrontClientSeeder::class);
    $user = User::factory()->registrationPayload('user');

    $response = $this->postJson('/register', $user);
    $response->assertStatus(201)
        ->assertJsonStructure(['data', 'message', 'token' => ['token_type', 'expires_in', 'access_token', 'refresh_token']])
        ->assertJsonPath('data.emailVerifiedAt', null);

    //The issued token signs the new user in with the default scope
    $this->withToken($response->json('token.access_token'))
        ->getJson('/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user['email']);

    //The refresh token belongs to the front client the front refreshes with
    $this->post('/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => Client::front()->first()->getKey(),
        'refresh_token' => $response->json('token.refresh_token'),
    ])->assertOk();

    //The front client keeps only its PKCE and refresh grants
    expect(Client::front()->first()->hasGrantType('password'))->toBeFalse();
});

test('user_registers_without_token_when_there_is_no_front_client', function () {
    $user = User::factory()->registrationPayload('user');

    $this->postJson('/register', $user)
        ->assertStatus(201)
        ->assertJsonMissingPath('token');
});

test('coffeeshop_registers_with_its_business_name', function () {
    $payload = [...User::factory()->registrationPayload('coffeeshop'), 'name' => 'Laura', 'businessName' => 'Nomad Coffee'];

    $this->postJson('/register', $payload)
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Laura')
        ->assertJsonPath('data.businessName', 'Nomad Coffee');

    //The person's name stays as it is; the business name has its own column
    $this->assertDatabaseHas('users', [
        'email' => $payload['email'],
        'name' => 'Laura',
        'surname' => $payload['surname'],
        'business_name' => 'Nomad Coffee',
    ]);
});

test('coffeeshop_registration_requires_a_valid_business_name', function (array $businessName) {
    $payload = User::factory()->registrationPayload('coffeeshop');
    unset($payload['businessName']);

    $this->postJson('/register', [...$payload, ...$businessName])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['businessName']);

    $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
})->with([
    'missing'  => [[]],
    'empty'    => [['businessName' => '']],
    'not text' => [['businessName' => ['Nomad Coffee']]],
    'too long' => [['businessName' => str_repeat('a', 151)]],
]);

test('registration_rejects_a_business_name_for_other_roles', function (string $role) {
    $payload = [...User::factory()->registrationPayload($role), 'businessName' => 'Nomad Coffee'];

    $this->postJson('/register', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['businessName']);

    $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
})->with(['specialist', 'user']);

test('specialist_registers_without_a_business_name', function () {
    $payload = User::factory()->registrationPayload('specialist');

    $this->postJson('/register', $payload)
        ->assertStatus(201)
        ->assertJsonPath('data.businessName', null);

    $this->assertDatabaseHas('users', ['email' => $payload['email'], 'business_name' => null]);
});

test('registration_grant_is_not_offered_by_the_token_endpoint', function () {
    $this->seed(FrontClientSeeder::class);
    $user = User::factory()->create();

    $this->post('/oauth/token', [
        'grant_type' => 'registration',
        'client_id' => Client::front()->first()->getKey(),
        'user_id' => $user->id,
    ])->assertStatus(400)
        ->assertJsonPath('error', 'unsupported_grant_type');
});

arch('registration_token_service_is_only_used_by_the_user_controller')
    ->expect(RegistrationTokenService::class)
    ->toOnlyBeUsedIn(UserController::class);

test('user_registers_and_receives_the_verification_email', function () {
    Notification::fake();
    $payload = User::factory()->registrationPayload('user');

    $this->postJson('/register', $payload)->assertStatus(201);

    Notification::assertSentTo(User::where('email', $payload['email'])->first(), VerifyEmail::class);
});

test('user_verifies_email_with_the_signed_link', function () {
    $user = User::factory()->unverified()->create();
    $url = call_user_func(VerifyEmail::$createUrlCallback, $user);

    //The link lands on the front's account page
    $this->actingAs($user, 'web')
        ->get($url)
        ->assertRedirect(config('fortify.redirects.email-verification') . '?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('verification_link_is_rejected_when_tampered_or_expired', function (string $case) {
    $user = User::factory()->unverified()->create();
    $url = call_user_func(VerifyEmail::$createUrlCallback, $user);

    if ($case === 'tampered') {
        $url .= '&lang=xx';
    } else {
        $this->travel(config('auth.verification.expire', 60) + 1)->minutes();
    }

    $this->actingAs($user, 'web')->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
})->with(['tampered', 'expired']);

test('authenticated_user_gets_the_email_verification_date', function () {
    [$user, $token] = authenticate();

    $this->withToken($token)
        ->getJson('/user')
        ->assertOk()
        ->assertJsonPath('data.emailVerifiedAt', $user->email_verified_at->toIso8601String());
});

test('user_authenticates_with_pkce', function () {

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
        'state' => $state,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
    ]);

    $codeRequest = $this->get('oauth/authorize?' . $query);

    $found = app(\Laravel\Passport\ClientRepository::class)->find($client->id);
    //dump(get_class($found));
    //dump($found->skipsAuthorization($user, []));

    $codeRequest->assertStatus(302);

    //$codeRequest->dumpHeaders();
    //$codeRequest->dump();

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

        //$tokenResponse->dump();
        $tokenResponse->assertOk();
    } else {
        test()->fail('CodeVerifiers could not be retreived from Location attribute in the header, or state is different');
    }
});

test('user_logs_out', function () {
    [$user, $token,, $response] = authenticate();

    //Validate token generation
    $response->assertOk();

    //Validate logout route
    $response = $this->withToken($token)->post('/logout');
    $response->assertOk();

    //Assert Token is revoked
    $this->assertDatabaseHas('oauth_access_tokens', [
        'user_id' => $user->id,
        'revoked' => true,
    ]);
});

test('authenticated_user_updates_field', function (string $field, string $value) {

    [$user, $token] = authenticateWithWriteScope();

    //Updating field using the api route

    $this->withToken($token)
        ->putJson("/users/{$user->ulid}", [$field => $value])
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

test('coffeeshop_updates_its_business_name', function () {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');
    $user->forceFill(['business_name' => 'Nomad Coffee'])->save();

    $this->withToken($token)
        ->putJson("/users/{$user->ulid}", ['businessName' => 'Nomad Coffee Roasters'])
        ->assertOk()
        ->assertJsonPath('data.businessName', 'Nomad Coffee Roasters')
        ->assertJsonPath('data.name', $user->name);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => $user->name,
        'business_name' => 'Nomad Coffee Roasters',
    ]);
});

test('coffeeshop_cannot_empty_or_overflow_its_business_name', function (mixed $value) {
    [$user, $token] = authenticateWithWriteScope('coffeeshop');
    $user->forceFill(['business_name' => 'Nomad Coffee'])->save();

    $this->withToken($token)
        ->putJson("/users/{$user->ulid}", ['businessName' => $value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['businessName']);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'business_name' => 'Nomad Coffee']);
})->with([
    'empty'    => [''],
    'null'     => [null],
    'too long' => [str_repeat('a', 151)],
]);

test('profile_update_rejects_a_business_name_for_other_roles', function (string $role) {
    [$user, $token] = authenticateWithWriteScope($role);

    $this->withToken($token)
        ->putJson("/users/{$user->ulid}", ['businessName' => 'Nomad Coffee'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['businessName']);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'business_name' => null]);
})->with(['specialist', 'user']);

test('authenticated_user_updates_password', function () {

    [$user, $token, $password] = authenticateWithWriteScope();

    //Updating password using the specific api route
    $this->withToken($token)
        ->putJson("/users/{$user->ulid}/password", [
            'currentPassword' => $password,
            'password' => 'nuevaClave1234',
            'passwordConfirmation' => 'nuevaClave1234',
        ])
        ->assertOk();

    // asserting the the password has been updated and hashed
    expect(Hash::check('nuevaClave1234', $user->fresh()->password))->toBeTrue();
});

test('authenticated_user_gets_profile_data', function () {
    [$user, $token] = authenticate();

    $this->withToken($token)
        ->getJson('/user')
        ->assertOk()
        ->assertJsonPath('data.ulid', $user->ulid);
});

test('authenticated_user_gets_the_business_name', function (string $role, ?string $businessName) {
    [$user, $token] = authenticate($role);
    $user->forceFill(['business_name' => $businessName])->save();

    $this->withToken($token)
        ->getJson('/user')
        ->assertOk()
        ->assertJsonPath('data.name', $user->name)
        ->assertJsonPath('data.businessName', $businessName);
})->with([
    'coffeeshop' => ['coffeeshop', 'Nomad Coffee'],
    'specialist' => ['specialist', null],
]);

test('authenticated_user_deactivates_profile', function () {
    [$user, $token] = authenticateWithWriteScope();

    $this->withToken($token)
        ->deleteJson("/users/{$user->ulid}")
        ->assertOk();

    $this->assertSoftDeleted($user);
});

test('authenticated_user_deletes_profile', function () {
    [$user, $token] = authenticateWithWriteScope();

    $this->withToken($token)
        ->deleteJson("/users/{$user->ulid}/force")
        ->assertOK();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
