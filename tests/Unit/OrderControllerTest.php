<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @group order-creation
     * @group authentication
     */
    public function test_unauthenticated_user_cannot_create_order()
    {
        // Act
        $response = $this->postJson('/api/orders', []);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     * @group order-creation
     * @group authentication
     */
    public function test_authenticated_user_can_create_order()
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
        $response->assertStatus(201);
    }


    /**
     * @test
     * @group order-creation
     * @group happy-path
     */
    public function test_order_is_created_successfully_with_valid_data_and_single_item()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 120, 'stock' => 5]);
        $orderData = [
            'cashier_id' => $user->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Order created successfully',
                'data' => [
                    'cashier_id' => $user->id,
                    'total' => 240,
                    'total_quantity' => 2,
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'cashier_id' => $user->id,
            'total' => 240,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /**
     * @test
     * @group order-creation
     * @group happy-path
     */
    public function test_order_is_created_successfully_with_multiple_items()
    {
        // Arrange
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 100, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 50, 'stock' => 5]);

        $orderData = [
            'cashier_id' => $user->id,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2], // 2 * 100 = 200
                ['product_id' => $product2->id, 'quantity' => 3], // 3 * 50 = 150
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.total', 350)
            ->assertJsonPath('data.total_quantity', 5);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
    }

    /**
     * @test
     * @group order-creation
     * @group happy-path
     */
    public function test_order_creation_response_returns_correct_json_structure_and_data()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);
        $orderData = [
            'cashier_id' => $user->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'transaction_number',
                    'cashier_id',
                    'total',
                    'total_quantity',
                    'payment_method',
                    'created_at',
                    'updated_at',
                    'order_items' => [
                        '*' => [
                            'id',
                            'order_id',
                            'product_id',
                            'quantity',
                            'product_price',
                            'total_item',
                            'created_at',
                            'updated_at',
                            'product' => [
                                'id',
                                'name',
                                'price',
                            ]
                        ]
                    ]
                ]
            ])
            ->assertJsonPath('data.total', 200)
            ->assertJsonPath('data.order_items.0.product_id', $product->id)
            ->assertJsonPath('data.order_items.0.quantity', 2)
            ->assertJsonPath('data.order_items.0.product_price', $product->price);
    }

    /**
     * @test
     * @group order-creation
     * @group happy-path
     */
    public function test_order_creation_generates_a_unique_transaction_number()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);
        $orderData = [
            'cashier_id' => $user->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        // Act
        $response1 = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);
        $response2 = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $trx1 = $response1->json('data.transaction_number');
        $trx2 = $response2->json('data.transaction_number');

        $this->assertNotNull($trx1);
        $this->assertStringStartsWith('TRX-', $trx1);
        $this->assertNotEquals($trx1, $trx2);
    }

    /**
     * @test
     * @group order-creation
     * @group happy-path
     */
    public function test_order_creation_saves_product_price_at_time_of_purchase()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);
        $orderData = [
            'cashier_id' => $user->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $orderData);
        $orderId = $response->json('data.id');

        // Change product price after order
        $product->price = 200;
        $product->save();

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product->id,
            'product_price' => 100, // Should be the original price
        ]);
    }

    // ... The rest of the tests from your list would follow a similar pattern ...
}