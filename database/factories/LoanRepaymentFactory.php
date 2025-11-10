<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanRepayment>
 */
class LoanRepaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => fake()->randomFloat(2, 1000, 10000),
            'payment_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'bank_deposit', 'mobile_money']),
            'reference' => fake()->optional(0.7)->bothify('TXN#######'),
            'received_by' => User::factory(),
            'remarks' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the repayment is cash payment.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
            'reference' => null,
        ]);
    }

    /**
     * Indicate that the repayment is bank transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'transfer',
            'reference' => fake()->bothify('TXN#######'),
        ]);
    }

    /**
     * Indicate that the repayment is bank deposit.
     */
    public function bankDeposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'bank_deposit',
            'reference' => fake()->bothify('DEP#######'),
        ]);
    }

    /**
     * Indicate that the repayment is mobile money.
     */
    public function mobileMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'mobile_money',
            'reference' => fake()->bothify('MM#######'),
        ]);
    }

    /**
     * Indicate that the repayment is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the repayment is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_date' => fake()->dateTimeBetween('-12 months', '-6 months'),
        ]);
    }
}
