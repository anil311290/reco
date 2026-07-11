<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'monthly_price' => fake()->randomFloat(2, 0, 999),
            'yearly_price' => fake()->randomFloat(2, 0, 9999),
            'lifetime_price' => 0,
            'currency' => 'INR',
            'trial_days' => 14,
            'max_users' => 5,
            'max_transactions' => 1000,
            'max_accounts' => 100,
            'max_parties' => 100,
            'features' => [],
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
            'is_visible' => true,
        ];
    }
}
