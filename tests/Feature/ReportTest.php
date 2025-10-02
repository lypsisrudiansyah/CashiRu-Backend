<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user to authenticate for the tests
        $this->user = User::factory()->create();
    }

    public function test_summary_requires_start_and_end_date()
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/reports/summary');

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message' => [
                    'end_date'
                ]
            ]);
    }

    public function test_summary_fails_when_end_date_before_start_date()
    {
        // Arrange
        $params = [
            'start_date' => '2023-10-01',
            'end_date' => '2023-09-30',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/reports/summary?' . http_build_query($params));
        // dd($response->json());
        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message' => [
                    'end_date'
                ]
            ]);
    }

    public function test_summary_returns_zero_when_no_orders_in_range()
    {
        // Arrange: Create an order outside the requested date range
        $product = Product::factory()->create();
        $order = Order::factory()->create(['created_at' => Carbon::yesterday()]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total_item' => 200,
        ]);

        $params = [
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/reports/summary?' . http_build_query($params));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'total_revenue' => 0,
                    'total_sold_quantity' => 0,
                ]
            ]);
    }

    public function test_summary_returns_correct_revenue_and_quantity()
    {
        // Arrange
        $product = Product::factory()->create();
        $order = Order::factory()->create(['total' => 250, 'created_at' => Carbon::now()]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'total_item' => 250,
        ]);

        $params = [
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/reports/summary?' . http_build_query($params));

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.total_revenue', 250)
            ->assertJsonPath('data.total_sold_quantity', 5);
    }

    public function test_summary_with_multiple_orders_and_items()
    {
        // Arrange
        $product = Product::factory()->create();
        $date = '2023-11-15';

        // Order 1 with 2 items
        $order1 = Order::factory()->create(['total' => 150, 'created_at' => Carbon::parse($date)]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'product_id' => $product->id, 'quantity' => 2, 'total_item' => 100]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'product_id' => $product->id, 'quantity' => 1, 'total_item' => 50]);

        // Order 2 with 1 item
        $order2 = Order::factory()->create(['total' => 200, 'created_at' => Carbon::parse($date)]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'product_id' => $product->id, 'quantity' => 4, 'total_item' => 200]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/reports/summary?start_date={$date}&end_date={$date}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.total_revenue', 350) // 150 + 200
            ->assertJsonPath('data.total_sold_quantity', 7); // 2 + 1 + 4
    }
}
