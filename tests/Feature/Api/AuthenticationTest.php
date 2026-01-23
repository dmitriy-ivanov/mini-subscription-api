<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set API key for testing
        config(['app.api_key' => 'test-api-key']);
    }

    public function test_subscription_endpoints_require_api_key(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $response = $this->postJson("/api/customers/{$customer->id}/subscriptions", [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
            ]);
    }

    public function test_subscription_endpoints_accept_x_api_key_header(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->postJson("/api/customers/{$customer->id}/subscriptions", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(201);
    }

    public function test_subscription_endpoints_accept_bearer_token(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $response = $this->withToken('test-api-key')
            ->postJson("/api/customers/{$customer->id}/subscriptions", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(201);
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->withHeader('X-API-Key', 'invalid-key')
            ->getJson("/api/customers/{$customer->id}/subscriptions");

        $response->assertStatus(401);
    }
}
