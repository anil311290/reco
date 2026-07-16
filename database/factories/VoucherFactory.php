<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense', 'receipt', 'payment', 'journal']);
        $prefix = match($type) {
            'income' => 'INC',
            'expense' => 'EXP',
            'receipt' => 'RCT',
            'payment' => 'PAY',
            'journal' => 'JRN',
            default => 'VCH',
        };

        return [
            'voucher_number' => $prefix . fake()->unique()->numerify('######'),
            'voucher_type' => $type,
            'voucher_date' => now()->toDateString(),
            'narration' => fake()->sentence(),
            'total_debit' => $amount = fake()->randomFloat(2, 100, 100000),
            'total_credit' => $amount,
            'status' => fake()->randomElement(['draft', 'posted']),
            'remarks' => fake()->sentence(),
        ];
    }
}
