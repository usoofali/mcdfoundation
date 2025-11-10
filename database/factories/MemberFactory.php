<?php

namespace Database\Factories;

use App\Models\ContributionPlan;
use App\Models\HealthcareProvider;
use App\Models\Lga;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $state = State::factory()->create();
        $lga = Lga::factory()->create(['state_id' => $state->id]);

        return [
            'registration_no' => 'MCDF/'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'full_name' => fake()->firstName(),
            'family_name' => fake()->lastName(),
            'date_of_birth' => fake()->date('Y-m-d', '2000-01-01'),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced']),
            'nin' => fake()->unique()->numerify('###########'),
            'occupation' => fake()->jobTitle(),
            'workplace' => fake()->company(),
            'address' => fake()->address(),
            'hometown' => fake()->city(),
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'country' => 'Nigeria',
            'healthcare_provider_id' => HealthcareProvider::factory(),
            'health_status' => fake()->optional()->sentence(),
            'contribution_plan_id' => ContributionPlan::factory(),
            'registration_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'status' => fake()->randomElement(['pre_registered', 'pending', 'active', 'inactive']),
            'created_by' => User::factory(),
            'is_complete' => fake()->boolean(80),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the member is pre-registered.
     */
    public function preRegistered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pre_registered',
            'is_complete' => false,
        ]);
    }

    /**
     * Indicate that the member is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'is_complete' => true,
        ]);
    }

    /**
     * Indicate that the member is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_complete' => true,
        ]);
    }

    /**
     * Indicate that the member is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'is_complete' => true,
        ]);
    }
}
