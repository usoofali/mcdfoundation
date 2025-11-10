<?php

namespace Database\Factories;

use App\Models\HealthcareProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HealthcareProvider>
 */
class HealthcareProviderFactory extends Factory
{
    protected $model = HealthcareProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' '.$this->faker->randomElement(['Hospital', 'Clinic', 'Medical Center', 'Health Center']),
            'address' => $this->faker->address(),
            'contact' => $this->faker->phoneNumber(),
            'services' => $this->faker->randomElements([
                'General Medicine',
                'Pediatrics',
                'Surgery',
                'Maternity',
                'Emergency Care',
                'Laboratory Services',
                'Pharmacy',
                'Radiology',
                'Dental Care',
                'Eye Care',
            ], $this->faker->numberBetween(3, 7)),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
