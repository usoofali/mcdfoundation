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
                'amount' => 100.00,
                'description' => 'Daily contribution plan - ₦100 per day',
                'active' => true,
            ],
            [
                'name' => 'weekly',
                'amount' => 700.00,
                'description' => 'Weekly contribution plan - ₦700 per week',
                'active' => true,
            ],
            [
                'name' => 'monthly',
                'amount' => 3000.00,
                'description' => 'Monthly contribution plan - ₦3,000 per month',
                'active' => true,
            ],
            [
                'name' => 'quarterly',
                'amount' => 9000.00,
                'description' => 'Quarterly contribution plan - ₦9,000 per quarter',
                'active' => true,
            ],
            [
                'name' => 'annual',
                'amount' => 36000.00,
                'description' => 'Annual contribution plan - ₦36,000 per year',
                'active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            ContributionPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
