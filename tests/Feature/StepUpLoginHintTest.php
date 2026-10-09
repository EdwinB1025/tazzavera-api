<?php

//EDB 10/09/26: a step-up with login_hint only issues the code to the hinted user

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Laravel\Passport\ClientRepository;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->password = 'testFakeUser1234';
    $this->user = User::factory()->create(['password' => $this->password]);
    $this->other = User::factory()->create(['password' => $this->password]);
    $this->client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'PKCE Login Hint Test',
        ['http://localhost/callback'],
        false
    );
});

function stepUpLoginAs(User $who, string $password, string $url)
{
    test()->get($url)->assertRedirect(route('login'));

    return test()->post(route('login.store'), [
        'email' => $who->email,
        'password' => $password,
    ]);
}

test('step_up_issues_the_code_to_the_hinted_user', function () {
    $this->actingAs($this->user, 'web');
    $url = authorizeUrl($this->client->id, 'profile:write', ['login_hint' => $this->user->ulid]);

    $login = stepUpLoginAs($this->user, $this->password, $url);

    assertRedirectsWithCode($this->get($login->headers->get('Location')));
});

test('step_up_by_another_user_is_denied_and_signed_out', function () {
    $this->actingAs($this->user, 'web');
    $url = authorizeUrl($this->client->id, 'profile:write', ['login_hint' => $this->user->ulid]);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $sent);

    $login = stepUpLoginAs($this->other, $this->password, $url);
    $response = $this->get($login->headers->get('Location'));

    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $answer);
    expect($response->headers->get('Location'))->toStartWith('http://localhost/callback?')
        ->and($answer['error'] ?? null)->toBe('access_denied')
        ->and($answer['state'] ?? null)->toBe($sent['state'])
        ->and($answer)->not->toHaveKey('code');
    $this->assertGuest('web');
});
