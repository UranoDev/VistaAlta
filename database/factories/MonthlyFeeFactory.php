<?php

namespace Database\Factories;

use App\Models\Fraccionamiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonthlyFee>
 */
class MonthlyFeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fraccionamiento_id' => Fraccionamiento::factory(),
            'amount' => fake()->randomElement([500, 600, 700, 800, 1000, 1200]),
            'start_date' => today(),
            'surcharge_type' => null,
            'surcharge_value' => null,
        ];
    }

    public function withPercentageSurcharge(float $percent = 10): static
    {
        return $this->state([
            'surcharge_type' => 'percentage',
            'surcharge_value' => $percent,
        ]);
    }

    public function withFixedSurcharge(float $amount = 80): static
    {
        return $this->state([
            'surcharge_type' => 'fixed',
            'surcharge_value' => $amount,
        ]);
    }

    public function future(): static
    {
        return $this->state([
            'start_date' => today()->addMonth(),
        ]);
    }
}
