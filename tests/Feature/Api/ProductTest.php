<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products_without_authentication(): void
    {
        Product::factory()->create(['name' => 'Daily News']);
        Product::factory()->create(['name' => 'Tech Journal']);
        Product::factory()->create(['name' => 'World Times']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_products_are_sorted_by_name(): void
    {
        Product::factory()->create(['name' => 'World Times']);
        Product::factory()->create(['name' => 'Daily News']);
        Product::factory()->create(['name' => 'Tech Journal']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);

        $products = $response->json('data');
        $this->assertEquals('Daily News', $products[0]['name']);
        $this->assertEquals('Tech Journal', $products[1]['name']);
        $this->assertEquals('World Times', $products[2]['name']);
    }
}
