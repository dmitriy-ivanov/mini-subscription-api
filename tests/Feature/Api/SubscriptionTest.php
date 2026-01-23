<?php

namespace Tests\Feature\Api;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set API key for testing
        config(['app.api_key' => 'test-api-key']);
    }

    public function test_can_subscribe_customer_to_product(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->postJson("/api/customers/{$customer->id}/subscriptions", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'customer_id',
                    'product' => [
                        'id',
                        'name',
                    ],
                    'status',
                    'subscribed_at',
                ],
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);
    }

    public function test_can_list_customer_subscriptions_returns_only_active_by_default(): void
    {
        $customer = Customer::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product1->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product2->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        // This cancelled subscription should not be returned by default
        Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product3->id,
            'status' => SubscriptionStatus::CANCELLED,
        ]);

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->getJson("/api/customers/{$customer->id}/subscriptions");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product' => [
                            'id',
                            'name',
                        ],
                        'status',
                        'subscribed_at',
                        'cancelled_at',
                        'created_at',
                    ],
                ],
            ]);

        // Verify all returned subscriptions are active
        $data = $response->json('data');
        foreach ($data as $subscription) {
            $this->assertEquals(SubscriptionStatus::ACTIVE->value, $subscription['status']);
        }
    }

    public function test_can_list_all_customer_subscriptions_with_all_parameter(): void
    {
        $customer = Customer::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product1->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product2->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product3->id,
            'status' => SubscriptionStatus::CANCELLED,
        ]);

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->getJson("/api/customers/{$customer->id}/subscriptions?all=true");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Verify we have both active and cancelled subscriptions
        $data = $response->json('data');
        $statuses = array_column($data, 'status');
        $this->assertContains(SubscriptionStatus::ACTIVE->value, $statuses);
        $this->assertContains(SubscriptionStatus::CANCELLED->value, $statuses);
    }

    public function test_can_unsubscribe_customer_from_product(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->deleteJson("/api/customers/{$customer->id}/subscriptions/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subscription cancelled successfully',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::CANCELLED->value,
        ]);
    }

    public function test_cannot_subscribe_to_invalid_product(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->postJson("/api/customers/{$customer->id}/subscriptions", [
                'product_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_cannot_subscribe_twice_to_same_product(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->postJson("/api/customers/{$customer->id}/subscriptions", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Subscription failed',
            ]);
    }

    public function test_cannot_unsubscribe_from_nonexistent_subscription(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->deleteJson("/api/customers/{$customer->id}/subscriptions/{$product->id}");

        $response->assertStatus(404);
    }

    public function test_subscription_requires_product_id(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->withHeader('X-API-Key', 'test-api-key')
            ->postJson("/api/customers/{$customer->id}/subscriptions", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }
}
