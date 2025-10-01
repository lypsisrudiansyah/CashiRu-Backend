<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_categories_successful()
    {
        // Create a user to authenticate
        $user = User::factory()->create();

        // Create some categories to test against
        $categories = Category::factory()->count(3)->create();

        // Act as the user and make the request
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories');

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Categories retrieved successfully',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'name', 'created_at', 'updated_at']
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_get_categories_unauthenticated()
    {
        // Make request without authentication
        $response = $this->getJson('/api/categories');

        // Assert the response is unauthorized
        $response->assertStatus(401);
    }
}