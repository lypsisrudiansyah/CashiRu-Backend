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
            ->assertJsonPath('data.cashier_id', $user->id)
            ->assertJsonPath('data.order_items.0.product_id', $product1->id)
            ->assertJsonPath('data.order_items.0.quantity', 2)
            ->assertJsonPath('data.order_items.0.total_item', 200)
            ->assertJsonPath('data.order_items.0.product_price', 100)
            ->assertJsonPath('data.order_items.1.product_id', $product2->id)
            ->assertJsonPath('data.order_items.1.quantity', 3)
            ->assertJsonPath('data.order_items.1.total_item', 150)
            ->assertJsonPath('data.order_items.1.product_price', 50);

        $this->assertDatabaseHas('orders', [
            'cashier_id' => $user->id,
            'total' => 350.00,
            'total_quantity' => 5,
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => Order::latest()->first()->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'total_item' => 200,
            'product_price' => 100,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => Order::latest()->first()->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'total_item' => 150,
            'product_price' => 50,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_an_order()
    {
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(401);
    }

    public function test_order_defaults_to_cash_payment_method_if_not_provided()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

        $orderData = [
            'cashier_id' => $user->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.payment_method', 'cash');

        $this->assertDatabaseHas('orders', [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
        ]);
    }

    public function test_authenticated_user_can_create_an_order_with_specified_payment_method()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'credit_card',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.payment_method', 'credit_card');

        $this->assertDatabaseHas('orders', [
            'cashier_id' => $user->id,
            'payment_method' => 'credit_card',
        ]);
    }

    public function test_order_creation_requires_cashier_id()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

        $orderData = [
            // 'cashier_id' => $user->id, // Missing cashier_id
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cashier_id']);
    }

    public function test_order_creation_requires_items_array()
    {
        // Arrange
        $user = User::factory()->create();

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            // 'items' => [], // Missing items array
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_order_creation_items_must_be_an_array()
    {
        // Arrange
        $user = User::factory()->create();

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            'items' => 'not an array', // items is not an array
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_order_creation_requires_product_id_in_items()
    {
        // Arrange
        $user = User::factory()->create();

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            'items' => [
                ['quantity' => 1], // Missing product_id
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_order_creation_product_id_must_exist()
    {
        // Arrange
        $user = User::factory()->create();

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => 999, 'quantity' => 1], // product_id does not exist
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_order_creation_requires_quantity_in_items()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id], // Missing quantity
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_order_creation_quantity_must_be_an_integer()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 'one'], // quantity is not an integer
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_order_creation_quantity_must_be_at_least_one()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 0], // quantity is less than 1
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }
}
