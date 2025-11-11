<?php

namespace Database\Factories;

use App\Models\ContributionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContributionPlan>
 */
class ContributionPlanFactory extends Factory
{
    protected $model = ContributionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plans = [
            'daily' => 100,
            'weekly' => 700,
            'monthly' => 3000,
            'quarterly' => 9000,
            'annual' => 36000,
        ];

        $frequency = $this->faker->randomElement(array_keys($plans));
        $amount = $plans[$frequency];

        return [
            'name' => $frequency,
            'display_name' => ucfirst($frequency).' Saver',
            'frequency' => $frequency,
            'amount' => $amount,
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'daily',
            'display_name' => 'Daily Saver',
            'frequency' => 'daily',
            'amount' => 100,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'weekly',
            'display_name' => 'Weekly Saver',
            'frequency' => 'weekly',
            'amount' => 700,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'monthly',
            'display_name' => 'Monthly Saver',
            'frequency' => 'monthly',
            'amount' => 3000,
        ]);
    }

    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'quarterly',
            'display_name' => 'Quarterly Saver',
            'frequency' => 'quarterly',
            'amount' => 9000,
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'annual',
            'display_name' => 'Annual Saver',
            'frequency' => 'annual',
            'amount' => 36000,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
