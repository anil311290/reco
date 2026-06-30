<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        return [
            'name' => $name,
            'slug' => str()->slug($name),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'India',
            'postal_code' => fake()->postcode(),
            'gst_number' => strtoupper(fake()->bothify('##?????####?#Z#')),
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'financial_year_start' => '04-01',
            'financial_year_end' => '03-31',
            'is_active' => true,
        ];
    }
}
