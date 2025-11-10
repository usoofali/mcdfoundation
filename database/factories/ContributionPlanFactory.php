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

        $name = $this->faker->randomElement(array_keys($plans));
        $amount = $plans[$name];

        return [
            'name' => $name,
            'amount' => $amount,
            'description' => $this->faker->sentence(),
            'active' => true,
        ];
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'daily',
            'amount' => 100,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'weekly',
            'amount' => 700,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'monthly',
            'amount' => 3000,
        ]);
    }

    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'quarterly',
            'amount' => 9000,
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'annual',
            'amount' => 36000,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
