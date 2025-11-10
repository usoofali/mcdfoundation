<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Approval>
 */
class ApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entityType = fake()->randomElement(['Loan', 'Claim', 'Registration']);
        $status = fake()->randomElement(['pending', 'approved', 'rejected']);

        return [
            'entity_type' => $entityType,
            'entity_id' => fake()->numberBetween(1, 100),
            'approved_by' => User::factory(),
            'role' => fake()->randomElement(['LG Coordinator', 'State Coordinator', 'Project Coordinator', 'Health Officer', 'Treasurer']),
            'approval_level' => fake()->numberBetween(1, 3),
            'status' => $status,
            'remarks' => fake()->optional(0.4)->sentence(),
            'approved_at' => $status !== 'pending' ? fake()->dateTimeBetween('-1 month', 'now') : null,
        ];
    }

    /**
     * Indicate that the approval is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the approval is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the approval is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the approval is for a loan.
     */
    public function loan(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => 'Loan',
            'entity_id' => fake()->numberBetween(1, 50),
        ]);
    }

    /**
     * Indicate that the approval is for a claim.
     */
    public function claim(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => 'Claim',
            'entity_id' => fake()->numberBetween(1, 50),
        ]);
    }

    /**
     * Indicate that the approval is for registration.
     */
    public function registration(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => 'Registration',
            'entity_id' => fake()->numberBetween(1, 50),
        ]);
    }

    /**
     * Indicate that the approval is at LG level.
     */
    public function lgLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_level' => 1,
            'role' => 'LG Coordinator',
        ]);
    }

    /**
     * Indicate that the approval is at State level.
     */
    public function stateLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_level' => 2,
            'role' => 'State Coordinator',
        ]);
    }

    /**
     * Indicate that the approval is at Project level.
     */
    public function projectLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_level' => 3,
            'role' => 'Project Coordinator',
        ]);
    }
}
