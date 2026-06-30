<?php

namespace Database\Factories;

use App\Models\VoucherLine;
use App\Models\Voucher;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherLineFactory extends Factory
{
    protected $model = VoucherLine::class;

    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'account_id' => Account::factory(),
            'debit' => fake()->randomFloat(2, 0, 10000),
            'credit' => 0,
            'description' => fake()->sentence(),
        ];
    }
}
