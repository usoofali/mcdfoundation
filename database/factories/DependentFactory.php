<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dependent>
 */
class DependentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $relationship = fake()->randomElement(['spouse', 'child', 'parent', 'sibling', 'other']);
        $dateOfBirth = fake()->dateTimeBetween('-80 years', '-1 year');

        return [
            'member_id' => Member::factory(),
            'name' => fake()->name(),
            'nin' => fake()->unique()->numerify(str_repeat('#', 11)),
            'date_of_birth' => $dateOfBirth,
            'relationship' => $relationship,
            'document_path' => fake()->optional(0.3)->filePath(),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the dependent is a child.
     */
    public function child(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => 'child',
            'date_of_birth' => fake()->dateTimeBetween('-15 years', '-1 year'),
        ]);
    }

    /**
     * Indicate that the dependent is a spouse.
     */
    public function spouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => 'spouse',
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
        ]);
    }

    /**
     * Indicate that the dependent is a parent.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => 'parent',
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-40 years'),
        ]);
    }

    /**
     * Indicate that the dependent is eligible.
     */
    public function eligible(): static
    {
        return $this->state(fn (array $attributes) => [
            'eligible' => true,
        ]);
    }

    /**
     * Indicate that the dependent is not eligible.
     */
    public function notEligible(): static
    {
        return $this->state(fn (array $attributes) => [
            'eligible' => false,
        ]);
    }
}
