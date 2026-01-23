<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Subscribe a customer to a product.
     *
     * @param  Customer  $customer
     * @param  int  $productId
     * @return Subscription
     * @throws \Exception
     */
    public function subscribe(Customer $customer, int $productId): Subscription
    {
        $product = Product::findOrFail($productId);

        // Check if there's already an active subscription
        $existingActive = Subscription::where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->first();

        if ($existingActive) {
            throw new \Exception('Customer already has an active subscription to this product.');
        }

        // Cancel any existing cancelled subscriptions (cleanup)
        Subscription::where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->where('status', SubscriptionStatus::CANCELLED)
            ->delete();

        try {
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'product_id' => $productId,
                'status' => SubscriptionStatus::ACTIVE,
                'subscribed_at' => now(),
            ]);

            return $subscription->load('product');
        } catch (UniqueConstraintViolationException $e) {
            throw new \Exception('Customer already has an active subscription to this product.');
        }
    }

    /**
     * Unsubscribe a customer from a product.
     *
     * @param  Customer  $customer
     * @param  int  $productId
     * @return bool
     */
    public function unsubscribe(Customer $customer, int $productId): bool
    {
        $subscription = Subscription::where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->firstOrFail();

        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);

        return true;
    }

    /**
     * Get subscriptions for a customer.
     *
     * @param  Customer  $customer
     * @param  bool  $all  If true, returns all subscriptions; if false, returns only active subscriptions
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomerSubscriptions(Customer $customer, bool $all = false)
    {
        $query = Subscription::where('customer_id', $customer->id)
            ->with('product')
            ->orderBy('created_at', 'desc');

        if (!$all) {
            $query->where('status', SubscriptionStatus::ACTIVE);
        }

        return $query->get();
    }
}
