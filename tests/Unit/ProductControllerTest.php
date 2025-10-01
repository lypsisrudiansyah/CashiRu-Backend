<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ProductController;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ProductController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ProductController();
    }

    public function test_index_returns_all_products()
    {
        // Create some products
        Product::factory()->count(5)->create();

        // Call the index method
        $response = $this->controller->index();
        $data = $response->getData(true);

        // Assertions
        $this->assertEquals(200, $response->status());
        $this->assertEquals('Products retrieved successfully', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(5, $data['data']);
    }

    public function test_index_returns_empty_array_when_no_products_exist()
    {
        // No products are created.

        // Call the index method
        $response = $this->controller->index();
        $data = $response->getData(true);

        // Assertions
        $this->assertEquals(200, $response->status());
        $this->assertEquals('Products retrieved successfully', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(0, $data['data']);
        $this->assertIsArray($data['data']);
    }

    public function test_index_returns_products_with_categories()
    {
        Product::factory()->create();

        $response = $this->controller->index();
        $data = $response->getData(true);

        $this->assertArrayHasKey('category', $data['data'][0]);
    }
}