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
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]);

        // Act
        $response = $this->controller->productSales($request);
        $data = $response->getData(true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $data['status']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Product', $data['data'][0]['product_name']);
        $this->assertEquals(3, $data['data'][0]['total_quantity']);
        $this->assertEquals(450, $data['data'][0]['total_item']);
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
}
