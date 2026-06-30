<?php

namespace Database\Factories;

use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['debtor', 'creditor']);
        $prefix = $type === 'debtor' ? 'DEB' : 'CRD';

        return [
            'party_code' => $prefix . fake()->unique()->numerify('####'),
            'name' => fake()->company(),
            'type' => $type,
            'mobile' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'India',
            'postal_code' => fake()->postcode(),
            'gstin' => strtoupper(fake()->bothify('##?????####?#Z#')),
            'opening_balance' => fake()->randomFloat(2, 0, 50000),
            'opening_date' => fake()->date(),
            'remarks' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
