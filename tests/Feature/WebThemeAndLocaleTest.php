<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Laravel\Passport\ClientRepository;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'PKCE Theme Test',
        ['http://localhost/callback'],
        false
    );
});

test('dark_theme_sets_data_theme_on_html', function () {
    $this->get('/forgot-password?theme=dark')
        ->assertOk()
        ->assertSee('data-theme="dark"', false);
});

test('catalan_sets_the_html_lang_and_the_texts', function () {
    $this->get('/forgot-password?lang=ca')
        ->assertOk()
        ->assertSee('lang="ca"', false)
        ->assertSee('Recupera la contrasenya');
});

test('unsupported_values_are_ignored', function () {
    $this->get('/forgot-password?theme=neon&lang=fr')
        ->assertOk()
        ->assertDontSee('data-theme=', false)
        ->assertSee('lang="es"', false)
        ->assertSee('Recuperar contraseña');
});

test('theme_and_language_survive_the_redirect_to_login', function () {
    $this->get(authorizeUrl($this->client->id, 'profile:read', ['theme' => 'dark', 'lang' => 'ca']))
        ->assertRedirect(route('login'));

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-theme="dark"', false)
        ->assertSee('lang="ca"', false)
        ->assertSee('Accedeix al teu compte');
});

test('authorize_with_theme_and_lang_returns_the_code', function () {
    $this->actingAs(User::factory()->create(), 'web');

    assertRedirectsWithCode(
        $this->get(authorizeUrl($this->client->id, 'profile:read', ['theme' => 'dark', 'lang' => 'ca']))
    );
});
