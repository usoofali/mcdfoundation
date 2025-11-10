<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Contribution rates in Naira
        Setting::firstOrCreate(
            ['key' => 'contribution_rates'],
            [
                'value' => [
                    'daily' => 100,
                    'weekly' => 700,
                    'monthly' => 3000,
                    'quarterly' => 9000,
                    'annual' => 36000,
                ],
                'description' => 'Contribution plan rates in Naira',
            ]
        );

        // Eligibility rules
        Setting::firstOrCreate(
            ['key' => 'eligibility_rules'],
            [
                'value' => [
                    'health_access_wait_days' => 60,
                    'surgery_eligibility_months' => 5,
                    'loan_eligibility_months' => 12,
                ],
                'description' => 'Health and loan eligibility rules',
            ]
        );

        // Fine settings
        Setting::firstOrCreate(
            ['key' => 'fine_settings'],
            [
                'value' => [
                    'late_payment_fine_percent' => 50,
                ],
                'description' => 'Late payment fine settings',
            ]
        );

        // Organization information
        Setting::firstOrCreate(
            ['key' => 'organization_info'],
            [
                'value' => [
                    'name' => 'MCDF Community Fund Initiative',
                    'address' => 'Nigeria',
                    'phone' => '',
                    'email' => '',
                    'website' => '',
                ],
                'description' => 'Organization contact information',
            ]
        );

        // System configuration
        Setting::firstOrCreate(
            ['key' => 'system_config'],
            [
                'value' => [
                    'max_file_size' => 2048, // KB
                    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf'],
                    'pagination_limit' => 25,
                    'session_timeout' => 120, // minutes
                ],
                'description' => 'System configuration settings',
            ]
        );

        // Health coverage settings
        Setting::firstOrCreate(
            ['key' => 'health_coverage'],
            [
                'value' => [
                    'outpatient_coverage_percent' => 90,
                    'inpatient_coverage_percent' => 90,
                    'surgery_coverage_percent' => 90,
                    'maternity_coverage_percent' => 90,
                    'member_copay_percent' => 10,
                ],
                'description' => 'Health coverage percentage settings',
            ]
        );

        // Loan settings
        Setting::firstOrCreate(
            ['key' => 'loan_settings'],
            [
                'value' => [
                    'max_loan_amount' => 100000, // Naira
                    'min_loan_amount' => 5000, // Naira
                    'max_repayment_period' => 24, // months
                    'min_repayment_period' => 1, // months
                ],
                'description' => 'Loan configuration settings',
            ]
        );
    }
}
