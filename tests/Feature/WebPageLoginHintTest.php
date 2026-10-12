<?php

//EDB 10/12/26: the security and email verification pages open only for the user hinted by the client

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->password = 'testFakeUser1234';
    $this->user = User::factory()->create(['password' => $this->password]);
    $this->other = User::factory()->create(['password' => $this->password]);
});

function hintedUrl(string $path, User $who): string
{
    return url($path) . '?' . http_build_query(['login_hint' => $who->ulid]);
}

function verificationLink(User $who): string
{
    return URL::temporarySignedRoute('verification.verify', Carbon::now()->addMinutes(60), [
        'id' => $who->getKey(),
        'hash' => sha1($who->getEmailForVerification()),
    ]);
}

test('security_page_with_another_users_session_signs_out_and_goes_to_login', function () {
    $this->actingAs($this->other, 'web')->withSession(['auth.password_confirmed_at' => time()]);
    $url = hintedUrl('/user/security', $this->user);

    $this->get($url)->assertRedirect($url);
    $this->assertGuest('web');

    $this->get($url)->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk();
});

test('security_page_is_not_served_when_another_user_signs_in', function () {
    $this->actingAs($this->other, 'web');
    $url = hintedUrl('/user/security', $this->user);
    $this->get($url)->assertRedirect($url);
    $this->get($url)->assertRedirect(route('login'));

    $login = $this->post(route('login.store'), [
        'email' => $this->other->email,
        'password' => $this->password,
    ]);
    $login->assertRedirect($url);

    $this->get($url)->assertRedirect($url);
    $this->assertGuest('web');
});

test('security_page_continues_when_the_hinted_user_signs_in', function () {
    $this->actingAs($this->other, 'web');
    $url = hintedUrl('/user/security', $this->user);
    $this->get($url)->assertRedirect($url);
    $this->get($url)->assertRedirect(route('login'));

    $login = $this->post(route('login.store'), [
        'email' => $this->user->email,
        'password' => $this->password,
    ]);
    $login->assertRedirect($url);

    $this->get($url)->assertRedirect(route('password.confirm'));
    $this->assertAuthenticatedAs($this->user, 'web');
});

test('security_page_opens_for_the_hinted_user', function () {
    $this->actingAs($this->user, 'web')->withSession(['auth.password_confirmed_at' => time()]);

    $this->get(hintedUrl('/user/security', $this->user))->assertOk();
    $this->assertAuthenticatedAs($this->user, 'web');
});

test('security_page_without_hint_is_unchanged', function () {
    $this->actingAs($this->other, 'web')->withSession(['auth.password_confirmed_at' => time()]);

    $this->get('/user/security')->assertOk();
    $this->assertAuthenticatedAs($this->other, 'web');
});

test('security_page_with_hint_and_no_session_goes_to_login', function () {
    $url = hintedUrl('/user/security', $this->user);

    $this->get($url)->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk();
});

test('verify_email_page_with_another_users_session_signs_out_and_goes_to_login', function () {
    $other = User::factory()->unverified()->create();
    $this->actingAs($other, 'web');
    $url = hintedUrl('/email/verify', $this->user);

    $this->get($url)->assertRedirect($url);
    $this->assertGuest('web');

    $this->get($url)->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk();
});

test('verify_email_page_opens_for_the_hinted_user', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user, 'web');

    $this->get(hintedUrl('/email/verify', $user))->assertOk();
    $this->assertAuthenticatedAs($user, 'web');
});

test('verify_email_page_without_hint_is_unchanged', function () {
    $other = User::factory()->unverified()->create();
    $this->actingAs($other, 'web');

    $this->get('/email/verify')->assertOk();
    $this->assertAuthenticatedAs($other, 'web');
});

test('verification_link_with_another_users_session_signs_out_and_goes_to_login', function () {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();
    $this->actingAs($other, 'web');
    $url = verificationLink($user) . '&' . http_build_query(['login_hint' => $user->ulid]);

    $this->get($url)->assertRedirect($url);
    $this->assertGuest('web');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification_link_without_hint_is_unchanged', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user, 'web');

    $this->get(verificationLink($user))->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user, 'web');
});
