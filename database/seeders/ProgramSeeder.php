<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a user to set as creator (prefer admin, fallback to first user)
        $creator = User::whereHas('role', function ($query) {
            $query->where('name', 'Super Admin');
        })->first() ?? User::first();

        if (!$creator) {
            $this->command->warn('No users found. Skipping program seeding.');
            return;
        }

        $programs = [
            [
                'name' => 'Tailoring & Fashion Design',
                'description' => 'Comprehensive training in garment construction, pattern making, and fashion design. Learn to create professional-quality clothing and start your own tailoring business.',
                'start_date' => now()->addMonths(1),
                'end_date' => now()->addMonths(7),
                'capacity' => 30,
                'is_active' => true,
                'eligibility_rules' => [
                    'min_contributions' => 3,
                    'min_age' => 16,
                    'max_age' => 50,
                ],
            ],
            [
                'name' => 'Hairdressing & Cosmetology',
                'description' => 'Professional training in hairdressing, makeup artistry, and beauty treatments. Gain skills for salon employment or starting your own beauty business.',
                'start_date' => now()->addMonths(2),
                'end_date' => now()->addMonths(8),
                'capacity' => 25,
                'is_active' => true,
                'eligibility_rules' => [
                    'min_contributions' => 2,
                    'min_age' => 18,
                ],
            ],
            [
                'name' => 'Computer Skills & Digital Literacy',
                'description' => 'Learn essential computer skills including MS Office, internet usage, email, and basic graphic design. Perfect for improving employability in the digital age.',
                'start_date' => now()->addWeeks(2),
                'end_date' => now()->addMonths(4),
                'capacity' => 40,
                'is_active' => true,
                'eligibility_rules' => [
                    'min_contributions' => 1,
                    'min_age' => 15,
                ],
            ],
            [
                'name' => 'Catering & Food Services',
                'description' => 'Professional culinary training covering food preparation, menu planning, food safety, and catering business management.',
                'start_date' => now()->addMonths(3),
                'end_date' => now()->addMonths(9),
                'capacity' => 20,
                'is_active' => true,
                'eligibility_rules' => [
                    'min_contributions' => 5,
                    'min_age' => 18,
                    'max_age' => 45,
                ],
            ],
            [
                'name' => 'Small Business Management',
                'description' => 'Learn the fundamentals of starting and managing a small business including bookkeeping, marketing, customer service, and financial planning.',
                'start_date' => now()->addMonths(1)->addWeeks(2),
                'end_date' => now()->addMonths(5),
                'capacity' => 35,
                'is_active' => true,
                'eligibility_rules' => [
                    'min_contributions' => 3,
                    'min_age' => 21,
                ],
            ],
            [
                'name' => 'Soap & Detergent Production',
                'description' => 'Hands-on training in manufacturing various types of soaps, detergents, and cleaning products. Includes business setup guidance.',
                'start_date' => now()->addMonths(2)->addWeeks(1),
                'end_date' => now()->addMonths(5)->addWeeks(1),
                'capacity' => 15,
                'is_active' => true,
                'eligibility_rules' => [
                    'min_contributions' => 2,
                    'min_age' => 18,
                ],
            ],
            [
                'name' => 'Advanced Accounting (Coming Soon)',
                'description' => 'Advanced bookkeeping and accounting skills for small businesses. Learn to use accounting software and prepare financial statements.',
                'start_date' => now()->addMonths(6),
                'end_date' => now()->addMonths(12),
                'capacity' => 20,
                'is_active' => false,
                'eligibility_rules' => [
                    'min_contributions' => 6,
                    'min_age' => 20,
                ],
            ],
        ];

        foreach ($programs as $programData) {
            Program::create([
                'name' => $programData['name'],
                'description' => $programData['description'],
                'start_date' => $programData['start_date'],
                'end_date' => $programData['end_date'],
                'capacity' => $programData['capacity'],
                'is_active' => $programData['is_active'],
                'eligibility_rules' => $programData['eligibility_rules'],
                'created_by' => $creator->id,
            ]);
        }

        $this->command->info('Programs seeded successfully.');
    }
}
