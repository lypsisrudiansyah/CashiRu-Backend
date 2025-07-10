<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_successful()
    {
        $user = User::factory()->create([
            'email' => 'rudi@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'rudi@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'user' => ['id', 'name', 'email'] // sesuaikan
            ]);
    }

    public function test_login_with_invalid_email()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'rudinew@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Email not found']);
    }

    public function test_login_with_wrong_password()
    {
        $user = User::factory()->create([
            'email' => 'rudi@example.com',
            'password' => bcrypt('rudipass'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'rudi@example.com',
            'password' => 'johndoe123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid password']);
    }

    public function test_logout_successful()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/logout');
        
        $tokenId = explode('|', $token)[0];
        
        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout successful']);
            
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_logout_without_token()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401); // Unauthorized
    }
}
