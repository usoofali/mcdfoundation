<?php

namespace Database\Factories;

use App\Models\ContributionPlan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contribution>
 */
class ContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentDate = fake()->dateTimeBetween('-1 year', 'now');
        $periodStart = fake()->dateTimeBetween($paymentDate, 'now');
        $periodEnd = fake()->dateTimeBetween($periodStart, '+1 month');

        return [
            'member_id' => Member::factory(),
            'contribution_plan_id' => ContributionPlan::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'bank_deposit', 'mobile_money']),
            'payment_reference' => fake()->optional(0.7)->bothify('TXN#######'),
            'payment_date' => $paymentDate,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => fake()->randomElement(['paid', 'pending', 'overdue']),
            'collected_by' => User::factory(),
            'fine_amount' => 0,
            'receipt_path' => fake()->optional(0.3)->filePath(),
            'uploaded_by' => fake()->optional(0.2)->randomElement([User::factory()]),
            'verification_notes' => fake()->optional(0.1)->sentence(),
            'verified_at' => fake()->optional(0.1)->dateTimeBetween('-6 months', 'now'),
            'verified_by' => fake()->optional(0.1)->randomElement([User::factory()]),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the contribution is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'fine_amount' => 0,
        ]);
    }

    /**
     * Indicate that the contribution is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'fine_amount' => 0,
        ]);
    }

    /**
     * Indicate that the contribution is overdue.
     */
    public function overdue(): static
    {
        $amount = fake()->randomFloat(2, 100, 5000);

        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'fine_amount' => $amount * 0.5, // 50% fine
            'payment_date' => fake()->dateTimeBetween('-6 months', '-1 month'),
            'period_end' => fake()->dateTimeBetween('-3 months', '-1 month'),
        ]);
    }

    /**
     * Indicate that the contribution has a fine.
     */
    public function withFine(): static
    {
        $amount = fake()->randomFloat(2, 100, 5000);

        return $this->state(fn (array $attributes) => [
            'fine_amount' => $amount * 0.5,
            'status' => 'overdue',
        ]);
    }

    /**
     * Indicate that the contribution is cash payment.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
            'payment_reference' => null,
        ]);
    }

    /**
     * Indicate that the contribution is bank transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'transfer',
            'payment_reference' => fake()->bothify('TXN#######'),
        ]);
    }

    /**
     * Indicate that the contribution was submitted by a member.
     */
    public function memberSubmitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'collected_by' => null,
            'uploaded_by' => User::factory(),
            'receipt_path' => fake()->filePath(),
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    /**
     * Indicate that the contribution is pending verification.
     */
    public function pendingVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'collected_by' => null,
            'uploaded_by' => User::factory(),
            'receipt_path' => fake()->filePath(),
            'verified_at' => null,
            'verified_by' => null,
            'verification_notes' => null,
        ]);
    }

    /**
     * Indicate that the contribution was verified and approved.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'collected_by' => User::factory(),
            'verified_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'verified_by' => User::factory(),
            'verification_notes' => fake()->optional(0.3)->sentence(),
        ]);
    }

    /**
     * Indicate that the contribution was verified and rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'collected_by' => User::factory(),
            'verified_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'verified_by' => User::factory(),
            'verification_notes' => fake()->sentence(),
        ]);
    }
}
