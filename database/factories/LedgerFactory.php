<?php

namespace Database\Factories;

use App\Models\Ledger;
use App\Models\Company;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

class LedgerFactory extends Factory
{
    protected $model = Ledger::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'financial_year_id' => FinancialYear::factory(),
            'account_id' => Account::factory(),
            'voucher_id' => Voucher::factory(),
            'transaction_date' => fake()->dateTime(),
            'reference_type' => 'voucher',
            'reference_id' => fake()->randomNumber(),
            'description' => fake()->sentence(),
            'debit' => fake()->randomFloat(2, 0, 10000),
            'credit' => 0,
            'running_balance' => fake()->randomFloat(2, 0, 100000),
            'balance_type' => 'debit',
        ];
    }
}
