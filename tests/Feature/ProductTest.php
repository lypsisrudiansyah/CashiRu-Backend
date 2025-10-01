<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_products_successful()
    {
        // Create a user to authenticate
        $user = User::factory()->create();

        // Create some products to test against
        Product::factory()->count(3)->create();

        // Act as the user and make the request
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/products');

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Products retrieved successfully',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id', 'name', 'category_id', 'price', 'stock', 'description', 'image', 'created_at', 'updated_at',
                        'category' => ['id', 'name', 'created_at', 'updated_at']
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_get_products_unauthenticated()
    {
        // Make request without authentication
        $response = $this->getJson('/api/products');

        // Assert the response is unauthorized
        $response->assertStatus(401);
    }
}
