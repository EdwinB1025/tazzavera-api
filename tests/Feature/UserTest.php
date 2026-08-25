<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

test('create_new_user', function (?string $role = 'user') {
    $user = User::factory()->registrationPayload($role);
    $response = $this->postJson('/register', $user);
    $response->dump();
    $response->assertStatus(201);
})->with(['user', 'coffeeshop', 'specialist']);
