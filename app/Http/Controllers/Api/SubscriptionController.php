<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Get subscriptions for a customer.
     * Returns only active subscriptions by default.
     * Pass ?all=true to get all subscriptions (including cancelled).
     */
    public function index(Customer $customer, Request $request): JsonResponse
    {
        $all = $request->boolean('all', false);
        $subscriptions = $this->subscriptionService->getCustomerSubscriptions($customer, $all);

        return response()->json([
            'data' => $subscriptions->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'product' => [
                        'id' => $subscription->product->id,
                        'name' => $subscription->product->name,
                    ],
                    'status' => $subscription->status,
                    'subscribed_at' => $subscription->subscribed_at,
                    'cancelled_at' => $subscription->cancelled_at,
                    'created_at' => $subscription->created_at,
                ];
            }),
        ]);
    }

    /**
     * Subscribe a customer to a product.
     */
    public function store(Customer $customer, SubscribeRequest $request): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->subscribe(
                $customer,
                $request->validated()['product_id']
            );

            return response()->json([
                'message' => 'Subscription created successfully',
                'data' => [
                    'id' => $subscription->id,
                    'customer_id' => $subscription->customer_id,
                    'product' => [
                        'id' => $subscription->product->id,
                        'name' => $subscription->product->name,
                    ],
                    'status' => $subscription->status,
                    'subscribed_at' => $subscription->subscribed_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Subscription failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unsubscribe a customer from a product.
     */
    public function destroy(Customer $customer, Product $product): JsonResponse
    {
        try {
            $this->subscriptionService->unsubscribe($customer, $product->id);

            return response()->json([
                'message' => 'Subscription cancelled successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'No active subscription found for this customer and product.',
            ], 404);
        }
    }
}
