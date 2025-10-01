<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_an_order()
    {
        // Arrange
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 100, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 50, 'stock' => 5]);

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2], // 2 * 100 = 200
                ['product_id' => $product2->id, 'quantity' => 3], // 3 * 50 = 150
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Order created successfully',
            ])
            ->assertJsonPath('data.total', 350)
            ->assertJsonPath('data.total_quantity', 5)
            ->assertJsonPath('data.cashier_id', $user->id);

        $order = Order::latest()->first();
        info($order->toArray());
        $this->assertDatabaseHas('orders', [
            'cashier_id' => $user->id,
            'total' => 350.00,
            'total_quantity' => 5,
        ]);

        $this->assertDatabaseHas('order_items', ['product_id' => $product1->id, 'quantity' => 2]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product2->id, 'quantity' => 3]);
    }

    public function test_unauthenticated_user_cannot_create_an_order()
    {
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(401);
    }
}
