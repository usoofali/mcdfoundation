<?php

use App\Models\HealthClaim;
use App\Models\HealthClaimDocument;
use App\Models\Member;
use App\Models\HealthcareProvider;
use App\Services\HealthClaimService;
use App\Services\HealthEligibilityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app', ['title' => 'Submit Health Claim'])] class extends Component {
    use AuthorizesRequests, WithFileUploads;
    public $member_id = '';
    public $healthcare_provider_id = '';
    public $claim_type = '';
    public $billed_amount = '';
    public $coverage_percent = 90.00;
    public $claim_date = '';
    public $documents = [];

    public $eligibility = null;
    public $showEligibility = false;

    public function mount(): void
    {
        $this->authorize('create', HealthClaim::class);
        $this->claim_date = now()->format('Y-m-d');
    }

    public function updatedMemberId(): void
    {
        $this->checkEligibility();
    }

    public function updatedClaimType(): void
    {
        $this->checkEligibility();
    }

    public function checkEligibility(): void
    {
        if ($this->member_id && $this->claim_type) {
            $eligibilityService = app(HealthEligibilityService::class);
            $member = Member::find($this->member_id);

            if ($member) {
                $this->eligibility = $eligibilityService->checkMemberEligibility($member, $this->claim_type);
                $this->showEligibility = true;
            }
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

    public function getMembersProperty()
    {
        return Member::where('status', 'active')->orderBy('full_name')->get();
    }

    public function getHealthcareProvidersProperty()
    {
        return HealthcareProvider::orderBy('name')->get();
    }

    public function getClaimTypesProperty()
    {
        $claimService = app(HealthClaimService::class);
        return $claimService->getClaimTypes();
    }

    public function submit(): void
    {
        $this->validate([
            'member_id' => 'required|exists:members,id',
            'healthcare_provider_id' => 'required|exists:healthcare_providers,id',
            'claim_type' => 'required|in:outpatient,inpatient,surgery,maternity',
            'billed_amount' => 'required|numeric|min:0',
            'claim_date' => 'required|date',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            $claimService = app(HealthClaimService::class);

            $claim = $claimService->submitClaim([
                'member_id' => $this->member_id,
                'healthcare_provider_id' => $this->healthcare_provider_id,
                'claim_type' => $this->claim_type,
                'billed_amount' => $this->billed_amount,
                'coverage_percent' => $this->coverage_percent,
                'claim_date' => $this->claim_date,
                'status' => 'submitted',
            ]);

            // Upload documents if any
            if (!empty($this->documents)) {
                foreach ($this->documents as $document) {
                    $path = $document->store('health-claims/' . $claim->id, 'local');

                    HealthClaimDocument::create([
                        'health_claim_id' => $claim->id,
                        'document_type' => 'other',
                        'file_path' => $path,
                        'file_name' => $document->getClientOriginalName(),
                        'file_size' => $document->getSize(),
                        'mime_type' => $document->getMimeType(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            session()->flash('success', 'Health claim submitted successfully.');
            $this->redirect(route('health-claims.show', $claim));
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Submit Health Claim
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Submit a new health claim for reimbursement
                </flux:text>
            </div>
            <div>
                <flux:button variant="ghost" href="{{ route('health-claims.index') }}" wire:navigate>
                    Back to Claims
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Eligibility Alert -->
    @if($showEligibility && $eligibility)
        @if($eligibility['eligible'])
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                <div class="flex items-start gap-3">
                    <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                    <div class="flex-1">
                        <flux:heading size="sm" class="font-medium text-green-900 dark:text-green-100">
                            Member is Eligible
                        </flux:heading>
                        <flux:text class="mt-1 text-sm text-green-700 dark:text-green-300">
                            This member meets all requirements for {{ $claim_type }} claims.
                        </flux:text>
                        <div class="mt-2 text-xs text-green-600 dark:text-green-400">
                            <div>• Days since registration: {{ $eligibility['days_since_registration'] }} days</div>
                            <div>• Contributions: {{ $eligibility['contribution_count'] }}
                                ({{ $eligibility['required_contributions'] }} required)</div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                <div class="flex items-start gap-3">
                    <flux:icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400" />
                    <div class="flex-1">
                        <flux:heading size="sm" class="font-medium text-red-900 dark:text-red-100">
                            Member Not Eligible
                        </flux:heading>
                        <flux:text class="mt-1 text-sm text-red-700 dark:text-red-300">
                            This member does not meet the requirements:
                        </flux:text>
                        <ul class="mt-2 list-disc list-inside text-xs text-red-600 dark:text-red-400">
                            @foreach($eligibility['issues'] as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Claim Form -->
    <form wire:submit="submit">
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="space-y-6">
                <!-- Member Selection -->
                <div>
                    <flux:label>Member *</flux:label>
                    <flux:select wire:model.live="member_id" required>
                        <option value="">Select Member</option>
                        @foreach($this->members as $member)
                            <option value="{{ $member->id }}">{{ $member->full_name }} ({{ $member->registration_no }})
                            </option>
                        @endforeach
                    </flux:select>
                    @error('member_id') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <!-- Claim Type -->
                <div>
                    <flux:label>Claim Type *</flux:label>
                    <flux:select wire:model.live="claim_type" required>
                        <option value="">Select Claim Type</option>
                        @foreach($this->claimTypes as $type => $details)
                            <option value="{{ $type }}">{{ $details['label'] }} - {{ $details['requirement'] }}</option>
                        @endforeach
                    </flux:select>
                    @error('claim_type') <flux:error>{{ $message }}</flux:error> @enderror

                    @if($claim_type && isset($this->claimTypes[$claim_type]))
                        <flux:text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $this->claimTypes[$claim_type]['description'] }} | Coverage:
                            {{ $this->claimTypes[$claim_type]['coverage'] }}
                        </flux:text>
                    @endif
                </div>

                <!-- Healthcare Provider -->
                <div>
                    <flux:label>Healthcare Provider *</flux:label>
                    <flux:select wire:model="healthcare_provider_id" required>
                        <option value="">Select Healthcare Provider</option>
                        @foreach($this->healthcareProviders as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('healthcare_provider_id') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <!-- Billed Amount -->
                <div>
                    <flux:label>Billed Amount (₦) *</flux:label>
                    <flux:input wire:model.live="billed_amount" type="number" step="0.01" min="0" placeholder="0.00"
                        required />
                    @error('billed_amount') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <!-- Financial Breakdown -->
                @if($billed_amount > 0)
                    <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-900">
                        <flux:heading size="sm" class="mb-3 font-medium text-neutral-900 dark:text-white">
                            Financial Breakdown
                        </flux:heading>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-neutral-600 dark:text-neutral-400">Billed Amount:</span>
                                <span
                                    class="font-medium text-neutral-900 dark:text-white">₦{{ number_format($billed_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-600 dark:text-neutral-400">Coverage
                                    ({{ $coverage_percent }}%):</span>
                                <span
                                    class="font-medium text-green-600 dark:text-green-400">₦{{ number_format($this->coveredAmount, 2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-neutral-200 pt-2 dark:border-neutral-700">
                                <span class="text-neutral-600 dark:text-neutral-400">Member Copay:</span>
                                <span
                                    class="font-medium text-neutral-900 dark:text-white">₦{{ number_format($this->copayAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Claim Date -->
                <div>
                    <flux:label>Claim Date *</flux:label>
                    <flux:input wire:model="claim_date" type="date" required />
                    @error('claim_date') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <!-- Document Upload -->
                <div>
                    <flux:label>Upload Documents (Optional)</flux:label>
                    <flux:text class="text-xs text-neutral-500 dark:text-neutral-400 mb-2">
                        Upload medical bills, receipts, or prescriptions (PDF, JPG, PNG - Max 5MB each)
                    </flux:text>
                    <input type="file" wire:model="documents" multiple accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-neutral-900 dark:text-white
                               file:mr-4 file:py-2 file:px-4
                               file:rounded-md file:border-0
                               file:text-sm file:font-semibold
                               file:bg-blue-50 file:text-blue-700
                               hover:file:bg-blue-100
                               dark:file:bg-blue-900 dark:file:text-blue-200">
                    @error('documents.*') <flux:error>{{ $message }}</flux:error> @enderror

                    <div wire:loading wire:target="documents" class="mt-2 text-sm text-blue-600">
                        Uploading...
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" href="{{ route('health-claims.index') }}" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary"
                        :disabled="$showEligibility && $eligibility && !$eligibility['eligible']">
                        Submit Claim
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>