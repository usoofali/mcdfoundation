<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update loan_settings with missing fields
        Setting::updateOrCreate(
            ['key' => 'loan_settings'],
            [
                'value' => [
                    'min_loan_amount' => 5000,
                    'max_loan_amount' => 100000,
                    'min_repayment_period' => 1,
                    'max_repayment_period' => 24,
                    'contribution_multiplier' => 2.0,
                    'min_contributions_for_loan' => 12,
                    'min_contribution_amount' => 10000,
                    'default_interest_rate' => 5.0,
                    'cash_loan_interest_rate' => 5.0,
                    'item_loan_interest_rate' => 7.0,
                    'late_payment_penalty_rate' => 2.0,
                    'allow_multiple_loans' => false,
                    'require_guarantor' => false,
                ],
                'description' => 'Loan configuration settings',
            ]
        );

        // Add health_eligibility settings
        Setting::updateOrCreate(
            ['key' => 'health_eligibility'],
            [
                'value' => [
                    'min_contributions_outpatient' => 1,
                    'min_contributions_inpatient' => 5,
                    'min_contributions_surgery' => 5,
                    'min_contributions_maternity' => 5,
                ],
                'description' => 'Health claim eligibility contribution requirements',
            ]
        );

        $this->command->info('Settings seeded successfully!');
    }
}
