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
        // Indian FY (Apr–Mar) covering "today" so period-lock tests stay valid.
        $startYear = (int) now()->format('n') >= 4
            ? (int) now()->format('Y')
            : (int) now()->format('Y') - 1;

        return [
            'name' => $startYear . '-' . ($startYear + 1),
            'start_date' => $startYear . '-04-01',
            'end_date' => ($startYear + 1) . '-03-31',
            'is_current' => false,
            'is_closed' => false,
        ];
    }
}
