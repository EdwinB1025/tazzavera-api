<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/** Account security: two-factor authentication and passkeys (Fortify + laravel/passkeys). */
Route::view('/user/security', 'livewire.auth.security')
    ->middleware(['auth:web', 'password.confirm'])
    ->name('security');
