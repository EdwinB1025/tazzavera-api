<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->password = 'testFakeUser1234';
    $this->user = User::factory()->create(['password' => $this->password]);
    $this->client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'PKCE Step-up Test',
        ['http://localhost/callback'],
        false
    );
});

/** Builds a PKCE authorization URL for the given scope. */
function authorizeUrl(string $clientId, string $scope): string
{
    $challenge = strtr(rtrim(base64_encode(hash('sha256', Str::random(128), true)), '='), '+/', '-_');

    return 'oauth/authorize?' . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => 'http://localhost/callback',
        'response_type' => 'code',
        'scope' => $scope,
        'state' => Str::random(40),
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ]);
}

/** Asserts the response redirects to the client's callback carrying a code. */
function assertRedirectsWithCode($response): void
{
    $response->assertStatus(302);

    $location = $response->headers->get('Location');
    expect($location)->toStartWith('http://localhost/callback');

    parse_str(parse_url($location, PHP_URL_QUERY), $params);
    expect($params)->toHaveKey('code');
}

test('write_scope_forces_a_single_login_with_an_open_session', function () {
    $this->actingAs($this->user, 'web');
    $url = authorizeUrl($this->client->id, 'profile:write');

    // First pass: the open session is closed and the user is sent to log in.
    $this->get($url)->assertRedirect(route('login'));
    $this->assertGuest('web');

    // Logging in returns to the authorization request...
    $login = $this->post(route('login.store'), [
        'email' => $this->user->email,
        'password' => $this->password,
    ]);
    $login->assertRedirectContains('oauth/authorize');

    // ...which now issues the code without asking for the login again.
    assertRedirectsWithCode($this->get($login->headers->get('Location')));
});

test('read_scope_does_not_force_login_with_an_open_session', function () {
    $this->actingAs($this->user, 'web');

    assertRedirectsWithCode($this->get(authorizeUrl($this->client->id, 'profile:read')));
    $this->assertAuthenticatedAs($this->user, 'web');
});

test('read_scope_does_not_force_login_with_a_remember_me_cookie', function () {
    $login = $this->post(route('login.store'), [
        'email' => $this->user->email,
        'password' => $this->password,
        'remember' => '1',
    ]);

    $recaller = Auth::guard('web')->getRecallerName();
    $cookie = $login->getCookie($recaller);
    expect($cookie)->not->toBeNull();

    // Drop the session so only the remember-me cookie identifies the user.
    $this->app['session']->flush();
    Auth::forgetGuards();

    $response = $this->withCookie($recaller, $cookie->getValue())
        ->get(authorizeUrl($this->client->id, 'profile:read'));

    assertRedirectsWithCode($response);
});
