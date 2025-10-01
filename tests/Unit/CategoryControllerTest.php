<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\CategoryController;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new CategoryController();
    }

    public function test_index_returns_all_categories()
    {
        // Create some categories
        Category::factory()->count(5)->create();

        // Call the index method
        $response = $this->controller->index();
        $data = $response->getData(true);

        // Assertions
        $this->assertEquals(200, $response->status());
        $this->assertEquals('Categories retrieved successfully', $data['message']);
        $this->assertGreaterThan(10, strlen($data['message']));
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(5, $data['data']);
    }

    public function test_index_returns_empty_array_when_no_categories_exist()
    {
        // No categories are created.

        // Call the index method
        $response = $this->controller->index();
        $data = $response->getData(true);

        // Assertions
        $this->assertEquals(200, $response->status());
        $this->assertEquals('Categories retrieved successfully', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(0, $data['data']);
        $this->assertIsArray($data['data']);
    }

    public function test_index_returns_correct_category_structure()
    {
        // Create a single category
        Category::factory()->create();

        // Call the index method
        $response = $this->controller->index();
        $data = $response->getData(true);

        // Assertions
        $this->assertCount(1, $data['data']);
        $category = $data['data'][0];
        $this->assertArrayHasKey('id', $category);
        $this->assertArrayHasKey('name', $category);
    }
}