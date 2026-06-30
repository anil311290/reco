<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialYear>
 */
class FinancialYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->year();
        
        return [
            'name' => $year . '-' . ($year + 1),
            'start_date' => $year . '-04-01',
            'end_date' => ($year + 1) . '-03-31',
            'is_current' => false,
            'is_closed' => false,
        ];
    }
}
