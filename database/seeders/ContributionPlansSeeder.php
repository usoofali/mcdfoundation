<?php

namespace Database\Seeders;

use App\Models\ContributionPlan;
use Illuminate\Database\Seeder;

class ContributionPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'daily',
                'display_name' => 'Daily Saver',
                'frequency' => 'daily',
                'amount' => 100.00,
                'description' => 'Daily contribution plan - ₦100 per day',
                'is_active' => true,
            ],
            [
                'name' => 'weekly',
                'display_name' => 'Weekly Saver',
                'frequency' => 'weekly',
                'amount' => 700.00,
                'description' => 'Weekly contribution plan - ₦700 per week',
                'is_active' => true,
            ],
            [
                'name' => 'monthly',
                'display_name' => 'Monthly Saver',
                'frequency' => 'monthly',
                'amount' => 3000.00,
                'description' => 'Monthly contribution plan - ₦3,000 per month',
                'is_active' => true,
            ],
            [
                'name' => 'quarterly',
                'display_name' => 'Quarterly Saver',
                'frequency' => 'quarterly',
                'amount' => 9000.00,
                'description' => 'Quarterly contribution plan - ₦9,000 per quarter',
                'is_active' => true,
            ],
            [
                'name' => 'annual',
                'display_name' => 'Annual Saver',
                'frequency' => 'annual',
                'amount' => 36000.00,
                'description' => 'Annual contribution plan - ₦36,000 per year',
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            ContributionPlan::updateOrCreate(['frequency' => $plan['frequency']], $plan);
        }
    }
}
