<?php

use App\Models\User;

test('create_new_user', function (?string $role = 'user') {
    $password = fake()->password(6, 8);
    $user = User::factory()->raw(['password' => $password]);
    $user = array_merge($user, ['role' => $role, 'password_confirmation' => $password]);
    $response = $this->postJson('/register', $user);
    $response->assertStatus(201);
})->with(['user', 'coffeeshop', 'specialist']);
