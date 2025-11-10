<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $loanType = fake()->randomElement(['cash', 'item']);
        $repaymentMode = fake()->randomElement(['installments', 'full']);
        $amount = fake()->randomFloat(2, 5000, 50000);

        return [
            'member_id' => Member::factory(),
            'loan_type' => $loanType,
            'item_description' => $loanType === 'item' ? fake()->sentence() : null,
            'amount' => $amount,
            'repayment_mode' => $repaymentMode,
            'installment_amount' => $repaymentMode === 'installments' ? $amount / 6 : null,
            'repayment_period' => fake()->randomElement(['6 months', '12 months', '18 months', '24 months']),
            'start_date' => fake()->dateTimeBetween('now', '+1 month'),
            'security_description' => fake()->optional(0.7)->paragraph(),
            'guarantor_name' => fake()->optional(0.8)->name(),
            'guarantor_contact' => fake()->optional(0.8)->phoneNumber(),
            'status' => fake()->randomElement(['pending', 'approved', 'disbursed', 'repaid', 'defaulted']),
            'approved_by' => User::factory(),
            'approval_date' => fake()->optional(0.6)->dateTimeBetween('-1 month', 'now'),
            'disbursement_date' => fake()->optional(0.4)->dateTimeBetween('-1 month', 'now'),
            'remarks' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the loan is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approval_date' => null,
            'disbursement_date' => null,
        ]);
    }

    /**
     * Indicate that the loan is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approval_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'disbursement_date' => null,
        ]);
    }

    /**
     * Indicate that the loan is disbursed.
     */
    public function disbursed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disbursed',
            'approval_date' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'disbursement_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the loan is repaid.
     */
    public function repaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'repaid',
            'approval_date' => fake()->dateTimeBetween('-6 months', '-5 months'),
            'disbursement_date' => fake()->dateTimeBetween('-5 months', '-4 months'),
        ]);
    }

    /**
     * Indicate that the loan is defaulted.
     */
    public function defaulted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'defaulted',
            'approval_date' => fake()->dateTimeBetween('-12 months', '-11 months'),
            'disbursement_date' => fake()->dateTimeBetween('-11 months', '-10 months'),
        ]);
    }

    /**
     * Indicate that the loan is a cash loan.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'loan_type' => 'cash',
            'item_description' => null,
        ]);
    }

    /**
     * Indicate that the loan is an item loan.
     */
    public function item(): static
    {
        return $this->state(fn (array $attributes) => [
            'loan_type' => 'item',
            'item_description' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the loan uses installment repayment.
     */
    public function installments(): static
    {
        return $this->state(fn (array $attributes) => [
            'repayment_mode' => 'installments',
            'installment_amount' => $attributes['amount'] / 6,
        ]);
    }

    /**
     * Indicate that the loan uses full repayment.
     */
    public function fullRepayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'repayment_mode' => 'full',
            'installment_amount' => null,
        ]);
    }
}
