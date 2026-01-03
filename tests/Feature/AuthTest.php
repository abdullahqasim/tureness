<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token()
    {
        $user = User::factory()->create(['password' => 'password123']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'email', 'token']]);
    }

    public function test_profile_requires_auth()
    {
        $response = $this->getJson('/api/v1/auth/user');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/user');

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_register_creates_user_and_returns_token()
    {
        $payload = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'phone_number' => '1234567890',
            'gender' => 'other',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'email', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }
}
