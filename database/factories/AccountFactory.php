<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['asset', 'liability', 'income', 'expense', 'equity']);
        $prefix = match($type) {
            'asset' => 'AST',
            'liability' => 'LIB',
            'income' => 'INC',
            'expense' => 'EXP',
            'equity' => 'EQT',
        };

        return [
            'account_code' => $prefix . fake()->unique()->numerify('####'),
            'account_name' => fake()->words(2, true),
            'account_type' => $type,
            'opening_balance' => fake()->randomFloat(2, 0, 100000),
            'opening_date' => fake()->date(),
            'remarks' => fake()->sentence(),
            'is_active' => true,
            'is_system' => false,
        ];
    }
}
