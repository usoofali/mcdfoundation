<?php

use App\Models\Setting;
use App\Services\LoanService;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Apply for Loan'])] class extends Component {
    public $loan_type = 'cash';
    public $item_description = '';
    public $amount = 0;
    public $purpose = '';
    public $repayment_mode = 'installments';
    public $repayment_period = '6 months';
    public $start_date = '';
    public $security_description = '';
    public $guarantor_name = '';
    public $guarantor_contact = '';
    public $remarks = '';

    public $loanEligibility = [];
    public $loanSettings = [];

    public function mount(): void
    {
        $this->start_date = now()->addDays(7)->format('Y-m-d');
        $this->calculateLoanEligibility();
        $this->loadLoanSettings();
    }

    public function loadLoanSettings(): void
    {
        $settings = Setting::where('key', 'loan_settings')->first();
        $this->loanSettings = $settings?->value ?? [
            'contribution_multiplier' => 2.0,
            'min_contributions_for_loan' => 12,
            'min_contribution_amount' => 10000,
            'default_interest_rate' => 5.0,
        ];
    }

    public function calculateLoanEligibility(): void
    {
        $member = auth()->user()->member;

        // Get member's contribution stats
        $totalContributions = $member->contributions()->where('status', 'paid')->sum('amount');
        $contributionCount = $member->contributions()->where('status', 'paid')->count();

        // Get loan settings
        $settings = Setting::where('key', 'loan_settings')->first();
        $loanSettings = $settings?->value ?? [];

        $multiplier = $loanSettings['contribution_multiplier'] ?? 2.0;
        $minContributions = $loanSettings['min_contributions_for_loan'] ?? 12;
        $minAmount = $loanSettings['min_contribution_amount'] ?? 10000;

        // Calculate maximum eligible loan
        $maxEligibleLoan = $totalContributions * $multiplier;

        // Check eligibility
        $eligible = $contributionCount >= $minContributions && $totalContributions >= $minAmount;

        $this->loanEligibility = [
            'eligible' => $eligible,
            'total_contributions' => $totalContributions,
            'contribution_count' => $contributionCount,
            'max_eligible_loan' => $maxEligibleLoan,
            'min_contributions_required' => $minContributions,
            'min_amount_required' => $minAmount,
            'multiplier' => $multiplier,
            'reasons' => $this->getIneligibilityReasons($contributionCount, $totalContributions, $minContributions, $minAmount),
        ];
    }

    protected function getIneligibilityReasons($contributionCount, $totalContributions, $minContributions, $minAmount): array
    {
        $reasons = [];

        if ($contributionCount < $minContributions) {
            $reasons[] = "You need at least {$minContributions} contributions (you have {$contributionCount})";
        }

        if ($totalContributions < $minAmount) {
            $reasons[] = "Your total contributions must be at least ₦" . number_format($minAmount, 2) . " (you have ₦" . number_format($totalContributions, 2) . ")";
        }

        return $reasons;
    }

    public function updatedLoanType(): void
    {
        if ($this->loan_type === 'cash') {
            $this->item_description = '';
        }
    }

    public function submitLoanApplication(LoanService $loanService): void
    {
        // Check eligibility first
        if (!$this->loanEligibility['eligible']) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You are not eligible for a loan at this time. ' . implode('. ', $this->loanEligibility['reasons']),
            ]);
            return;
        }

        // Check if amount exceeds eligible amount
        if ($this->amount > $this->loanEligibility['max_eligible_loan']) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Loan amount exceeds your maximum eligible amount of ₦' . number_format($this->loanEligibility['max_eligible_loan'], 2),
            ]);
            return;
        }

        $this->validate([
            'loan_type' => 'required|in:cash,item',
            'item_description' => 'required_if:loan_type,item|nullable|string|max:255',
            'amount' => 'required|numeric|min:1000',
            'purpose' => 'required|string|max:500',
            'repayment_mode' => 'required|in:installments,full',
            'repayment_period' => 'required|string|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'security_description' => 'nullable|string|max:1000',
            'guarantor_name' => 'nullable|string|max:150',
            'guarantor_contact' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            // Auto-use logged-in member
            $member = auth()->user()->member;

            $data = [
                'member_id' => $member->id,  // Auto-populated!
                'loan_type' => $this->loan_type,
                'item_description' => $this->item_description,
                'amount' => $this->amount,
                'purpose' => $this->purpose,
                'repayment_mode' => $this->repayment_mode,
                'repayment_period' => $this->repayment_period,
                'start_date' => $this->start_date,
                'security_description' => $this->security_description,
                'guarantor_name' => $this->guarantor_name,
                'guarantor_contact' => $this->guarantor_contact,
                'remarks' => $this->remarks,
            ];

            $loan = $loanService->createLoanApplication($data);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Loan application submitted successfully. Application ID: #' . $loan->id,
            ]);

            $this->redirect(route('my.loans.show', $loan), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to submit loan application: ' . $e->getMessage(),
            ]);
        }
    }

    public function getRepaymentPeriodOptionsProperty(): array
    {
        return [
            '3 months' => '3 months',
            '6 months' => '6 months',
            '12 months' => '12 months',
            '18 months' => '18 months',
            '24 months' => '24 months',
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Apply for Loan') }}</h2>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Loan Eligibility Calculator -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Loan Eligibility Calculator') }}
            </h3>

            @if($loanEligibility['eligible'])
                <div
                    class="bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 p-4 mb-4">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <p class="font-medium text-green-900 dark:text-green-100">
                                {{ __('You are eligible for a loan!') }}
                            </p>
                            <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                                {{ __('Based on your contributions, you can borrow up to:') }}
                            </p>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100 mt-2">
                                ₦{{ number_format($loanEligibility['max_eligible_loan'], 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 p-4 mb-4">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <p class="font-medium text-red-900 dark:text-red-100">{{ __('Not eligible for a loan yet') }}
                            </p>
                            <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                                @foreach($loanEligibility['reasons'] as $reason)
                                    <li>{{ $reason }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Contribution Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-neutral-50 dark:bg-neutral-900 rounded-lg p-4">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Total Contributions') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        ₦{{ number_format($loanEligibility['total_contributions'], 2) }}</p>
                </div>
                <div class="bg-neutral-50 dark:bg-neutral-900 rounded-lg p-4">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Contributions Made') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $loanEligibility['contribution_count'] }}
                    </p>
                </div>
                <div class="bg-neutral-50 dark:bg-neutral-900 rounded-lg p-4">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Multiplier') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $loanEligibility['multiplier'] }}×
                    </p>
                </div>
            </div>

            <div class="mt-4 text-xs text-neutral-600 dark:text-neutral-400">
                <p>{{ __('Formula: Total Contributions × Multiplier = Maximum Eligible Loan') }}</p>
                <p class="mt-1">{{ __('₦') }}{{ number_format($loanEligibility['total_contributions'], 2) }} ×
                    {{ $loanEligibility['multiplier'] }} =
                    ₦{{ number_format($loanEligibility['max_eligible_loan'], 2) }}
                </p>
            </div>
        </div>

        <!-- Loan Application Form -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <form wire:submit="submitLoanApplication" class="space-y-6">
                <!-- Loan Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:select wire:model.live="loan_type" label="Loan Type" required>
                            <option value="cash">{{ __('Cash Loan') }}</option>
                            <option value="item">{{ __('Item Loan') }}</option>
                        </flux:select>
                        @error('loan_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="amount" type="number" step="0.01" label="Loan Amount (₦)"
                            placeholder="e.g., 50000.00" required />
                        @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Item Description (if item loan) -->
                @if($loan_type === 'item')
                    <div>
                        <flux:textarea wire:model="item_description" label="Item Description"
                            placeholder="Describe the item you want to purchase" required />
                        @error('item_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                @endif

                <!-- Purpose -->
                <div>
                    <flux:textarea wire:model="purpose" label="Purpose of Loan"
                        placeholder="Explain why you need this loan" required />
                    @error('purpose') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Repayment Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <flux:select wire:model.live="repayment_mode" label="Repayment Mode" required>
                            <option value="installments">{{ __('Installments') }}</option>
                            <option value="full">{{ __('Full Payment') }}</option>
                        </flux:select>
                        @error('repayment_mode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:select wire:model="repayment_period" label="Repayment Period" required>
                            @foreach($this->repaymentPeriodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        @error('repayment_period') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="start_date" type="date" label="Start Date" required />
                        @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Security/Collateral -->
                <div>
                    <flux:textarea wire:model="security_description" label="Security/Collateral (Optional)"
                        placeholder="Describe any security or collateral you can provide" />
                    @error('security_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Guarantor Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input wire:model="guarantor_name" label="Guarantor Name (Optional)"
                            placeholder="Full name of guarantor" />
                        @error('guarantor_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="guarantor_contact" label="Guarantor Contact (Optional)"
                            placeholder="Phone number or email" />
                        @error('guarantor_contact') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <flux:textarea wire:model="remarks" label="Additional Remarks (Optional)"
                        placeholder="Any additional information" />
                    @error('remarks') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <flux:button :href="route('my.loans')" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Submit Application') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <!-- Info Card -->
        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4">
            <div class="flex gap-3">
                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    <p class="font-medium mb-1">{{ __('Loan Application Process') }}</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-300">
                        <li>{{ __('Your application will be reviewed by the management team') }}</li>
                        <li>{{ __('You will be notified once your application is approved or rejected') }}</li>
                        <li>{{ __('Approved loans will be disbursed according to the schedule') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>