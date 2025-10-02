<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ReportController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ReportController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ReportController();
    }

    public function test_summary_returns_correct_data()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100]);
        $order = Order::factory()->create(['total' => 200, 'created_at' => Carbon::now()]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total_item' => 200,
        ]);

        $request = new Request([
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]);

        // Act
        $response = $this->controller->summary($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $data['status']);
        $this->assertEquals(200, $data['data']['total_revenue']);
        $this->assertEquals(2, $data['data']['total_sold_quantity']);
    }

    public function test_summary_with_no_orders_in_date_range()
    {
        // Arrange
        // Create an order outside the requested date range
        $product = Product::factory()->create(['price' => 100]);
        $order = Order::factory()->create(['total' => 200, 'created_at' => Carbon::yesterday()]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total_item' => 200,
        ]);

        $request = new Request([
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]);

        // Act
        $response = $this->controller->summary($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $data['status']);
        $this->assertEquals(0, $data['data']['total_revenue']);
        $this->assertEquals(0, $data['data']['total_sold_quantity']);
    }

    public function test_summary_returns_validation_error_for_invalid_dates()
    {
        $request = new Request(['start_date' => '2023-10-01', 'end_date' => '2023-09-30']);

        $response = $this->controller->summary($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_summary_returns_validation_error_for_missing_dates()
    {
        // Arrange
        $request = new Request();

        // Act
        $response = $this->controller->summary($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('start_date', $data['message']);
        $this->assertArrayHasKey('end_date', $data['message']);
    }

    public function test_summary_includes_boundary_timestamps()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100]);
        $start = Carbon::createFromFormat('Y-m-d H:i:s', '2023-10-01 00:00:00');
        $end = Carbon::createFromFormat('Y-m-d H:i:s', '2023-10-07 23:59:59');

        $orderStart = Order::factory()->create(['total' => 100, 'created_at' => $start]);
        $orderEnd = Order::factory()->create(['total' => 200, 'created_at' => $end]);
        Order::factory()->create([
            'total' => 300,
            'created_at' => Carbon::createFromFormat('Y-m-d H:i:s', '2025-10-08 00:00:00')
        ]);
        OrderItem::factory()->create([
            'order_id' => $orderStart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'total_item' => 100,
        ]);
        OrderItem::factory()->create([
            'order_id' => $orderEnd->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total_item' => 200,
        ]);

        $request = new Request([
            'start_date' => '2023-10-01',
            'end_date' => '2023-10-07',
        ]);

        // Act
        $response = $this->controller->summary($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(300, $data['data']['total_revenue']);
        $this->assertEquals(3, $data['data']['total_sold_quantity']);
    }

    public function test_summary_aggregates_multiple_orders_and_items()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 50]);
        $o1 = Order::factory()->create(['total' => 100, 'created_at' => Carbon::parse('2023-11-10 10:00:00')]);
        $o2 = Order::factory()->create(['total' => 50, 'created_at' => Carbon::parse('2023-11-10 15:00:00')]);

        OrderItem::factory()->create([
            'order_id' => $o1->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'total_item' => 50,
        ]);
        OrderItem::factory()->create([
            'order_id' => $o1->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'total_item' => 50,
        ]);
        OrderItem::factory()->create([
            'order_id' => $o2->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'total_item' => 150,
        ]);

        $request = new Request([
            'start_date' => '2023-11-10',
            'end_date' => '2023-11-10',
        ]);

        // Act
        $response = $this->controller->summary($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(150, $data['data']['total_revenue']);
        $this->assertEquals(5, $data['data']['total_sold_quantity']);
    }

    public function test_summary_excludes_orders_outside_range_even_if_items_in_range()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 10]);
        $orderOutside = Order::factory()->create(['total' => 999, 'created_at' => Carbon::parse('2023-12-01 12:00:00')]);

        // Item timestamps shouldn't matter; filter is on orders.created_at
        OrderItem::factory()->create([
            'order_id' => $orderOutside->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'total_item' => 100,
            'created_at' => Carbon::parse('2023-11-15 10:00:00'),
        ]);

        $request = new Request([
            'start_date' => '2023-11-01',
            'end_date' => '2023-11-30',
        ]);

        // Act
        $response = $this->controller->summary($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(0, $data['data']['total_revenue']);
        $this->assertEquals(0, $data['data']['total_sold_quantity']);
    }

    public function test_summary_accepts_same_start_and_end_date()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 20]);
        $order = Order::factory()->create(['total' => 20, 'created_at' => Carbon::parse('2023-09-09 09:00:00')]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'total_item' => 20,
        ]);

        $request = new Request([
            'start_date' => '2023-09-09',
            'end_date' => '2023-09-09',
        ]);

        // Act
        $response = $this->controller->summary($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(20, $data['data']['total_revenue']);
        $this->assertEquals(1, $data['data']['total_sold_quantity']);
    }

    public function test_summary_returns_validation_error_for_wrong_date_format()
    {
        // Arrange: wrong format (slashes instead of dashes)
        $request = new Request([
            'start_date' => '2023/01/01',
            'end_date' => '2023/01/02',
        ]);

        // Act
        $response = $this->controller->summary($request);
        $data = $response->getData(true);
        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('start_date', $data['message']);
        $this->assertArrayHasKey('end_date', $data['message']);
    }

    public function test_product_sales_returns_correct_data()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 150, 'name' => 'Test Product']);
        $order = Order::factory()->create(['created_at' => Carbon::now()]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'total_item' => 450,
            'created_at' => Carbon::now(),
        ]);

        $request = new Request([
            'start_date' => Carbon::now()->subHour(1)->toDateString(),
            'end_date' => Carbon::now()->endOfDay()->toDateString(),
        ]);

        // Act
        $response = $this->controller->productSales($request);
        $data = $response->getData(true);
        // dd($data, $request  ->all());

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $data['status']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Product', $data['data'][0]['product_name']);
        $this->assertEquals(3, $data['data'][0]['total_quantity']);
        $this->assertEquals(450, $data['data'][0]['total_item']);
    }

    public function test_product_sales_with_no_data_in_date_range()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 150, 'name' => 'Test Product']);
        $order = Order::factory()->create(['created_at' => Carbon::yesterday()]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'total_item' => 450,
            'created_at' => Carbon::yesterday(),
        ]);

        $request = new Request(['start_date' => Carbon::now()->toDateString(), 'end_date' => Carbon::now()->toDateString()]);

        $response = $this->controller->productSales($request);
        $data = $response->getData(true);
        $this->assertCount(0, $data['data']);
    }

    public function test_product_sales_returns_validation_error_for_invalid_dates()
    {
        // Arrange
        $request = new Request(['start_date' => '2023-10-01', 'end_date' => '2023-09-30']);

        // Act
        $response = $this->controller->productSales($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('end_date', $data['message']);
    }

    public function test_product_sales_returns_validation_error_for_missing_dates()
    {
        // Arrange
        $request = new Request();

        // Act
        $response = $this->controller->productSales($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('start_date', $data['message']);
    }
}
