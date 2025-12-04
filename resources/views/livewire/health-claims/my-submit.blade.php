<?php

use App\Models\HealthcareProvider;
use App\Services\HealthClaimService;
use App\Services\HealthEligibilityService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app', ['title' => 'Submit Health Claim'])] class extends Component {
    use WithFileUploads;

    public $healthcare_provider_id = '';
    public $claim_type = '';
    public $billed_amount = '';
    public $coverage_percent = 90.00;
    public $claim_date = '';
    public $documents = [];
    public $diagnosis = '';
    public $treatment = '';

    public $eligibility = null;
    public $showEligibility = false;

    public function mount(): void
    {
        $this->claim_date = now()->format('Y-m-d');
        $this->checkEligibility();
    }

    public function updatedClaimType(): void
    {
        $this->checkEligibility();
    }

    public function checkEligibility(): void
    {
        if ($this->claim_type) {
            $eligibilityService = app(HealthEligibilityService::class);
            $member = auth()->user()->member;

            $this->eligibility = $eligibilityService->checkMemberEligibility($member, $this->claim_type);
            $this->showEligibility = true;
        } else {
            $this->showEligibility = false;
        }
    }

    public function getCoveredAmountProperty()
    {
        if (!$this->billed_amount) {
            return 0;
        }
        return $this->billed_amount * ($this->coverage_percent / 100);
    }

    public function getCopayAmountProperty()
    {
        if (!$this->billed_amount) {
            return 0;
        }
        return $this->billed_amount - $this->coveredAmount;
    }

    public function getHealthcareProvidersProperty()
    {
        return HealthcareProvider::orderBy('name')->get();
    }

    public function getClaimTypesProperty()
    {
        $claimService = app(HealthClaimService::class);
        $types = $claimService->getClaimTypes();

        // Extract just the labels for the dropdown
        return collect($types)->mapWithKeys(function ($type, $key) {
            return [$key => $type['label']];
        })->toArray();
    }

    public function submit(): void
    {
        $this->validate([
            'healthcare_provider_id' => 'required|exists:healthcare_providers,id',
            'claim_type' => 'required|in:outpatient,inpatient,surgery,maternity',
            'billed_amount' => 'required|numeric|min:0',
            'claim_date' => 'required|date',
            'diagnosis' => 'required|string|max:500',
            'treatment' => 'required|string|max:1000',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            // Auto-use logged-in member
            $member = auth()->user()->member;

            $claimService = app(HealthClaimService::class);

            $data = [
                'member_id' => $member->id,  // Auto-populated!
                'healthcare_provider_id' => $this->healthcare_provider_id,
                'claim_type' => $this->claim_type,
                'billed_amount' => $this->billed_amount,
                'coverage_percent' => $this->coverage_percent,
                'covered_amount' => $this->coveredAmount,
                'copay_amount' => $this->copayAmount,
                'claim_date' => $this->claim_date,
                'diagnosis' => $this->diagnosis,
                'treatment' => $this->treatment,
            ];

            $claim = $claimService->createClaim($data, $this->documents);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Health claim submitted successfully. Claim Number: ' . $claim->claim_number,
            ]);

            $this->redirect(route('my.claims.show', $claim), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to submit claim: ' . $e->getMessage(),
            ]);
        }
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Submit Health Claim') }}</h2>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Eligibility Alert -->
        @if($showEligibility)
            <div
                class="rounded-xl border p-4 {{ $eligibility['eligible'] ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' }}">
                <div class="flex gap-3">
                    @if($eligibility['eligible'])
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                    <div
                        class="text-sm {{ $eligibility['eligible'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                        <p class="font-medium">{{ $eligibility['message'] }}</p>
                        @if(!empty($eligibility['details']))
                            <ul class="mt-1 list-disc list-inside space-y-1">
                                @foreach($eligibility['details'] as $detail)
                                    <li>{{ $detail }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Claim Form -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <form wire:submit="submit" class="space-y-6">
                <!-- Claim Type & Provider -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:select wire:model.live="claim_type" label="Claim Type" required>
                            <option value="">{{ __('Select claim type') }}</option>
                            @foreach($this->claimTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        @error('claim_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:select wire:model="healthcare_provider_id" label="Healthcare Provider" required>
                            <option value="">{{ __('Select provider') }}</option>
                            @foreach($this->healthcareProviders as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('healthcare_provider_id') <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Billed Amount & Claim Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input wire:model.live="billed_amount" type="number" step="0.01" label="Billed Amount (₦)"
                            placeholder="e.g., 25000.00" required />
                        @error('billed_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="claim_date" type="date" label="Claim Date" required />
                        @error('claim_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Coverage Calculation -->
                @if($billed_amount > 0)
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">{{ __('Coverage Breakdown') }}
                        </h4>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-blue-700 dark:text-blue-300">{{ __('Billed Amount') }}</p>
                                <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                                    ₦{{ number_format($billed_amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-blue-700 dark:text-blue-300">{{ __('Covered (90%)') }}</p>
                                <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                                    ₦{{ number_format($this->coveredAmount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-blue-700 dark:text-blue-300">{{ __('Your Copay (10%)') }}</p>
                                <p class="text-lg font-semibold text-orange-600 dark:text-orange-400">
                                    ₦{{ number_format($this->copayAmount, 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Diagnosis -->
                <div>
                    <flux:textarea wire:model="diagnosis" label="Diagnosis"
                        placeholder="Describe the medical condition or diagnosis" required />
                    @error('diagnosis') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Treatment -->
                <div>
                    <flux:textarea wire:model="treatment" label="Treatment Received"
                        placeholder="Describe the treatment, medications, or procedures" required />
                    @error('treatment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Documents -->
                <div>
                    <flux:label>{{ __('Supporting Documents (Optional)') }}</flux:label>
                    <input type="file" wire:model="documents" multiple accept=".pdf,.jpg,.jpeg,.png"
                        class="mt-1 block w-full text-sm text-gray-900 dark:text-white border border-neutral-300 dark:border-neutral-600 rounded-lg cursor-pointer bg-neutral-50 dark:bg-neutral-900 focus:outline-none" />
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        {{ __('Upload receipts, prescriptions, or medical reports (PDF, JPG, PNG - Max 5MB each)') }}
                    </p>
                    @error('documents.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <flux:button :href="route('my.claims')" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit" :disabled="!$eligibility || !$eligibility['eligible']">
                        {{ __('Submit Claim') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <!-- Info Card -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4">
            <div class="flex gap-3">
                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    <p class="font-medium mb-1">{{ __('Claim Submission Guidelines') }}</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-300">
                        <li>{{ __('Ensure you are eligible for the claim type before submitting') }}</li>
                        <li>{{ __('Upload all supporting documents (receipts, prescriptions, reports)') }}</li>
                        <li>{{ __('Claims are typically processed within 5-7 business days') }}</li>
                        <li>{{ __('You will be notified once your claim is approved or requires additional information') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>