<?php

use App\Models\Setting;
use Livewire\Volt\Component;

new class extends Component {
    public array $settings = [];
    public array $formData = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $this->settings = Setting::all()->keyBy('key')->toArray();

        // Initialize form data with current settings
        $this->formData = [
            'contribution_rates' => $this->settings['contribution_rates']['value'] ?? [],
            'eligibility_rules' => $this->settings['eligibility_rules']['value'] ?? [],
            'fine_settings' => $this->settings['fine_settings']['value'] ?? [],
            'organization_info' => $this->settings['organization_info']['value'] ?? [],
            'system_config' => $this->settings['system_config']['value'] ?? [],
            'health_coverage' => $this->settings['health_coverage']['value'] ?? [],
            'loan_settings' => $this->settings['loan_settings']['value'] ?? [],
            'program_settings' => $this->settings['program_settings']['value'] ?? [],
            'cashout_settings' => $this->settings['cashout_settings']['value'] ?? [],
        ];
    }

    public function save(): void
    {
        $this->validate([
            'formData.contribution_rates' => ['array'],
            'formData.eligibility_rules' => ['array'],
            'formData.fine_settings' => ['array'],
            'formData.organization_info' => ['array'],
            'formData.system_config' => ['array'],
            'formData.health_coverage' => ['array'],
            'formData.loan_settings' => ['array'],
            'formData.program_settings' => ['array'],
            'formData.cashout_settings' => ['array'],
        ]);

        foreach ($this->formData as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Settings updated successfully.',
        ]);
    }

    public function resetToDefaults(): void
    {
        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => 'This feature will be implemented in the next version.',
        ]);
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="leading-tight text-xl font-semibold text-zinc-800 dark:text-zinc-200">{{ __('System Settings') }}
        </h2>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" icon="arrow-path" wire:click="resetToDefaults" variant="outline"
                class="gap-2">

                {{ __('Reset to Defaults') }}
            </flux:button>
            <flux:button variant="primary" icon="check" wire:click="save" primary class="gap-2">

                {{ __('Save Settings') }}
            </flux:button>
        </div>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
        <div
            class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="save" class="space-y-8">
                <!-- Contribution Rates -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('Contribution Rates') }}
                    </flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <flux:input wire:model="formData.contribution_rates.daily" type="number"
                            label="{{ __('Daily Rate (₦)') }}" placeholder="100" />
                        <flux:input wire:model="formData.contribution_rates.weekly" type="number"
                            label="{{ __('Weekly Rate (₦)') }}" placeholder="700" />
                        <flux:input wire:model="formData.contribution_rates.monthly" type="number"
                            label="{{ __('Monthly Rate (₦)') }}" placeholder="3000" />
                        <flux:input wire:model="formData.contribution_rates.quarterly" type="number"
                            label="{{ __('Quarterly Rate (₦)') }}" placeholder="9000" />
                        <flux:input wire:model="formData.contribution_rates.annual" type="number"
                            label="{{ __('Annual Rate (₦)') }}" placeholder="36000" />
                    </div>
                </div>

                <!-- Eligibility Rules -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('Eligibility Rules') }}
                    </flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:input wire:model="formData.eligibility_rules.health_access_wait_days" type="number"
                            label="{{ __('Health Access Wait Period (Days)') }}" placeholder="60" />
                        <flux:input wire:model="formData.eligibility_rules.surgery_eligibility_months" type="number"
                            label="{{ __('Surgery Eligibility (Months)') }}" placeholder="5" />
                        <flux:input wire:model="formData.eligibility_rules.loan_eligibility_months" type="number"
                            label="{{ __('Loan Eligibility (Months)') }}" placeholder="12" />
                    </div>
                </div>

                <!-- Fine Settings -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('Fine Settings') }}
                    </flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="formData.fine_settings.late_payment_fine_percent" type="number"
                            label="{{ __('Late Payment Fine (%)') }}" placeholder="50" />
                    </div>
                </div>

                <!-- Organization Information -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('Organization Information') }}
                    </flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="formData.organization_info.name" label="{{ __('Organization Name') }}"
                            placeholder="MCDF Community Fund Initiative" />
                        <flux:input wire:model="formData.organization_info.email" type="email" label="{{ __('Email') }}"
                            placeholder="info@mcdf.org" />
                        <flux:input wire:model="formData.organization_info.phone" label="{{ __('Phone') }}"
                            placeholder="+234-xxx-xxx-xxxx" />
                        <flux:input wire:model="formData.organization_info.website" label="{{ __('Website') }}"
                            placeholder="https://mcdf.org" />
                        <flux:textarea wire:model="formData.organization_info.address" label="{{ __('Address') }}"
                            placeholder="Enter organization address" rows="3" />
                    </div>
                </div>

                <!-- Health Coverage Settings -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('Health Coverage Settings') }}
                    </flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <flux:input wire:model="formData.health_coverage.outpatient_coverage_percent" type="number"
                            label="{{ __('Outpatient Coverage (%)') }}" placeholder="90" />
                        <flux:input wire:model="formData.health_coverage.inpatient_coverage_percent" type="number"
                            label="{{ __('Inpatient Coverage (%)') }}" placeholder="90" />
                        <flux:input wire:model="formData.health_coverage.surgery_coverage_percent" type="number"
                            label="{{ __('Surgery Coverage (%)') }}" placeholder="90" />
                        <flux:input wire:model="formData.health_coverage.maternity_coverage_percent" type="number"
                            label="{{ __('Maternity Coverage (%)') }}" placeholder="90" />
                        <flux:input wire:model="formData.health_coverage.member_copay_percent" type="number"
                            label="{{ __('Member Copay (%)') }}" placeholder="10" />
                    </div>
                </div>

                <!-- Loan Settings -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('Loan Settings') }}
                    </flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <flux:input wire:model="formData.loan_settings.min_loan_amount" type="number"
                            label="{{ __('Minimum Loan Amount (₦)') }}" placeholder="5000" />
                        <flux:input wire:model="formData.loan_settings.max_loan_amount" type="number"
                            label="{{ __('Maximum Loan Amount (₦)') }}" placeholder="100000" />
                        <flux:input wire:model="formData.loan_settings.min_repayment_period" type="number"
                            label="{{ __('Min Repayment Period (Months)') }}" placeholder="1" />
                        <flux:input wire:model="formData.loan_settings.max_repayment_period" type="number"
                            label="{{ __('Max Repayment Period (Months)') }}" placeholder="24" />
                    </div>
                </div>

                <!-- Program Settings -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('Program Settings') }}
                    </flux:heading>
                    <flux:text class="mb-4 text-sm text-neutral-600 dark:text-neutral-400">
                        Configure default settings for vocational training programs
                    </flux:text>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <flux:input wire:model="formData.program_settings.default_min_contributions" type="number"
                            label="{{ __('Default Min Contributions') }}" placeholder="3" />
                        <flux:input wire:model="formData.program_settings.default_min_age" type="number"
                            label="{{ __('Default Minimum Age') }}" placeholder="16" />
                        <flux:input wire:model="formData.program_settings.default_max_age" type="number"
                            label="{{ __('Default Maximum Age') }}" placeholder="65" />
                        <flux:input wire:model="formData.program_settings.default_capacity" type="number"
                            label="{{ __('Default Program Capacity') }}" placeholder="30" />
                        <div>
                            <flux:checkbox wire:model="formData.program_settings.certificate_enabled"
                                label="{{ __('Enable Certificate Issuance') }}" />
                        </div>
                        <div>
                            <flux:checkbox wire:model="formData.program_settings.auto_enroll_enabled"
                                label="{{ __('Enable Auto-Enrollment') }}" />
                        </div>
                    </div>
                </div>

                <!-- Cashout Settings -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('Cashout Settings') }}
                    </flux:heading>
                    <flux:text class="mb-4 text-sm text-neutral-600 dark:text-neutral-400">
                        Configure member cashout eligibility and processing
                    </flux:text>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <flux:input wire:model="formData.cashout_settings.min_membership_months" type="number"
                            label="{{ __('Minimum Membership (Months)') }}" placeholder="12" />
                        <flux:input wire:model="formData.cashout_settings.min_contributions_required" type="number"
                            label="{{ __('Min Contributions Required') }}" placeholder="6" />
                        <flux:input wire:model="formData.cashout_settings.cashout_processing_fee_percent" type="number"
                            step="0.01" label="{{ __('Processing Fee (%)') }}" placeholder="0" />
                        <div>
                            <flux:checkbox wire:model="formData.cashout_settings.allow_partial_cashout"
                                label="{{ __('Allow Partial Cashout') }}" />
                        </div>
                    </div>
                </div>

                <!-- System Configuration -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                        {{ __('System Configuration') }}
                    </flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <flux:input wire:model="formData.system_config.max_file_size" type="number"
                            label="{{ __('Max File Size (KB)') }}" placeholder="2048" />
                        <flux:input wire:model="formData.system_config.pagination_limit" type="number"
                            label="{{ __('Pagination Limit') }}" placeholder="25" />
                        <flux:input wire:model="formData.system_config.session_timeout" type="number"
                            label="{{ __('Session Timeout (Minutes)') }}" placeholder="120" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <flux:button wire:click="resetToDefaults" variant="outline" class="w-full gap-2 sm:w-auto">
                        <flux:icon name="arrow-path" class="size-4" />
                        {{ __('Reset to Defaults') }}
                    </flux:button>

                    <flux:button type="submit" primary class="w-full gap-2 sm:w-auto">
                        <flux:icon name="check" class="size-4" />
                        {{ __('Save Settings') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>