<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    public function set(string $key, mixed $value, ?string $description = null): void
    {
        Setting::set($key, $value, $description);
    }

    public function getContributionRates(): array
    {
        return $this->get('contribution_rates', [
            'daily' => 100,
            'weekly' => 700,
            'monthly' => 3000,
            'quarterly' => 9000,
            'annual' => 36000,
        ]);
    }

    public function getEligibilityRules(): array
    {
        return $this->get('eligibility_rules', [
            'health_access_wait_days' => 60,
            'surgery_eligibility_months' => 5,
            'loan_eligibility_months' => 12,
        ]);
    }

    public function getFineSettings(): array
    {
        return $this->get('fine_settings', [
            'late_payment_fine_percent' => 50,
        ]);
    }

    public function updateContributionRates(array $rates): void
    {
        $this->set('contribution_rates', $rates, 'Contribution plan rates in Naira');
    }

    public function updateEligibilityRules(array $rules): void
    {
        $this->set('eligibility_rules', $rules, 'Health and loan eligibility rules');
    }

    public function updateFineSettings(array $settings): void
    {
        $this->set('fine_settings', $settings, 'Late payment fine settings');
    }
}
