<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'product_id' => \App\Models\Product::factory(),
            'status' => SubscriptionStatus::ACTIVE,
            'subscribed_at' => now(),
            'cancelled_at' => null,
        ];
    }
}
