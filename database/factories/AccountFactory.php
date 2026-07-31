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
        $range = match ($type) {
            'asset' => [1000, 1999],
            'liability' => [2000, 2999],
            'equity' => [3000, 3999],
            'income' => [4000, 4999],
            'expense' => [5000, 5999],
            default => [9000, 9999],
        };

        return [
            'account_code' => (string) fake()->unique()->numberBetween($range[0], $range[1]),
            'account_name' => fake()->words(2, true),
            'account_type' => $type,
            'is_cash_bank_od' => false,
            'opening_balance' => fake()->randomFloat(2, 0, 100000),
            'balance_type' => in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit',
            'opening_date' => fake()->date(),
            'remarks' => fake()->sentence(),
            'is_active' => true,
            'is_system' => false,
            'entry_source' => 'manual',
        ];
    }
}
