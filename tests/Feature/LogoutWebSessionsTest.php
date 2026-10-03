<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    // Web sessions are stored in the database, as in the application.
    config(['session.driver' => 'database']);

    $this->password = 'testFakeUser1234';
    $this->user = User::factory()->create(['password' => $this->password]);
    $this->client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'PKCE Logout Test',
        ['http://localhost/callback'],
        false
    );
});

test('api_logout_ends_the_web_sessions_of_the_user', function () {
    // Web login (OAuth login page) with "remember me".
    $login = $this->post(route('login.store'), [
        'email' => $this->user->email,
        'password' => $this->password,
        'remember' => '1',
    ]);

    $sessionCookie = $login->getCookie(config('session.cookie'));
    $recaller = Auth::guard('web')->getRecallerName();
    $rememberCookie = $login->getCookie($recaller);
    $rememberToken = $this->user->fresh()->remember_token;

    // The web session row is identified by the web guard's user.
    expect(DB::table('sessions')->where('user_id', $this->user->id)->count())->toBe(1);

    // Log out from the front-end through the API.
    Passport::actingAs($this->user, ['profile:read']);
    $this->postJson('/logout')->assertOk();

    expect(DB::table('sessions')->where('user_id', $this->user->id)->count())->toBe(0);
    expect($this->user->fresh()->remember_token)->not->toBe($rememberToken);

    // The browser comes back with its old cookies: authorize asks for the login.
    // (A test keeps the session store and the guards in memory between
    // requests; clearing them makes the next request read the database, as a
    // new request does in the application.)
    app('session.store')->flush();
    Auth::forgetGuards();

    $this->withCookie(config('session.cookie'), $sessionCookie->getValue())
        ->withCookie($recaller, $rememberCookie->getValue())
        ->get(authorizeUrl($this->client->id, 'profile:read'))
        ->assertRedirect(route('login'));
});
