<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'user_test',
            'email' => 'user_test@example.com',
            'password' => 'password123',
        ]);

        $register->assertStatus(201);
        $register->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => ['id', 'username', 'email', 'role', 'activationStatus'],
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'user_test@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk();
        $login->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => ['id', 'username', 'email', 'role', 'activationStatus'],
        ]);
    }
}

