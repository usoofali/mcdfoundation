<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FundLedger>
 */
class FundLedgerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['inflow', 'outflow']);
        $sources = $type === 'inflow'
            ? ['contribution', 'loan_repayment', 'donation', 'fine_collection']
            : ['claim_payment', 'loan_disbursement', 'refund', 'expense'];

        return [
            'type' => $type,
            'member_id' => fake()->optional(0.8)->randomElement(Member::pluck('id')),
            'source' => fake()->randomElement($sources),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'description' => fake()->sentence(),
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'reference' => fake()->optional(0.7)->bothify('REF#######'),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the entry is an inflow.
     */
    public function inflow(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'inflow',
            'source' => fake()->randomElement(['contribution', 'loan_repayment', 'donation', 'fine_collection']),
        ]);
    }

    /**
     * Indicate that the entry is an outflow.
     */
    public function outflow(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'outflow',
            'source' => fake()->randomElement(['claim_payment', 'loan_disbursement', 'refund', 'expense']),
        ]);
    }

    /**
     * Indicate that the entry is a contribution.
     */
    public function contribution(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'inflow',
            'source' => 'contribution',
            'description' => 'Member contribution - '.fake()->sentence(),
            'reference' => fake()->bothify('RCP#######'),
        ]);
    }

    /**
     * Indicate that the entry is a loan repayment.
     */
    public function loanRepayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'inflow',
            'source' => 'loan_repayment',
            'description' => 'Loan repayment - '.fake()->sentence(),
            'reference' => fake()->bothify('LOAN#######'),
        ]);
    }

    /**
     * Indicate that the entry is a claim payment.
     */
    public function claimPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'outflow',
            'source' => 'claim_payment',
            'description' => 'Health claim payment - '.fake()->sentence(),
            'reference' => fake()->bothify('CLAIM#######'),
        ]);
    }

    /**
     * Indicate that the entry is a loan disbursement.
     */
    public function loanDisbursement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'outflow',
            'source' => 'loan_disbursement',
            'description' => 'Loan disbursement - '.fake()->sentence(),
            'reference' => fake()->bothify('LOAN#######'),
        ]);
    }
}
