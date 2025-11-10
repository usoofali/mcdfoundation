<?php

use App\Models\Member;
use App\Models\State;
use App\Models\Lga;
use App\Models\HealthcareProvider;
use App\Models\ContributionPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app', ['title' => 'Edit Member'])] class extends Component {
    use WithFileUploads;

    public Member $member;

    // Form data
    public string $full_name = '';
    public string $family_name = '';
    public string $date_of_birth = '';
    public string $marital_status = '';
    public string $nin = '';
    public string $occupation = '';
    public string $workplace = '';
    public string $address = '';
    public string $hometown = '';
    public string $state_id = '';
    public string $lga_id = '';
    public string $country = 'Nigeria';
    public string $healthcare_provider_id = '';
    public string $health_status = '';
    public string $contribution_plan_id = '';
    public string $phone = '';
    public string $email = '';
    public $photo;
    public string $notes = '';
    public string $status = '';

    // Form state
    public int $currentStep = 1;
    public bool $isComplete = false;

    // Options
    public $states;
    public $lgas = [];
    public $healthcareProviders;
    public $contributionPlans;

    public function mount(Member $member): void
    {
        $this->member = $member;
        
        // Check authorization
        if (!auth()->user()->can('update', $member)) {
            abort(403, 'You do not have permission to edit this member.');
        }

        $this->states = State::orderBy('name')->get();
        $this->healthcareProviders = HealthcareProvider::orderBy('name')->get();
        $this->contributionPlans = ContributionPlan::orderBy('name')->get();

        // Load member data
        $this->loadMemberData();
    }

    public function loadMemberData(): void
    {
        $this->full_name = $this->member->full_name;
        $this->family_name = $this->member->family_name;
        $this->date_of_birth = $this->member->date_of_birth?->format('Y-m-d') ?? '';
        $this->marital_status = $this->member->marital_status ?? '';
        $this->nin = $this->member->nin ?? '';
        $this->occupation = $this->member->occupation ?? '';
        $this->workplace = $this->member->workplace ?? '';
        $this->address = $this->member->address ?? '';
        $this->hometown = $this->member->hometown ?? '';
        $this->state_id = $this->member->state_id ?? '';
        $this->lga_id = $this->member->lga_id ?? '';
        $this->country = $this->member->country ?? 'Nigeria';
        $this->healthcare_provider_id = $this->member->healthcare_provider_id ?? '';
        $this->health_status = $this->member->health_status ?? '';
        $this->contribution_plan_id = $this->member->contribution_plan_id ?? '';
        $this->phone = $this->member->user?->phone ?? '';
        $this->email = $this->member->user?->email ?? '';
        $this->notes = $this->member->notes ?? '';
        $this->status = $this->member->status ?? '';
        $this->isComplete = $this->member->is_complete;

        // Load LGAs for selected state
        if ($this->state_id) {
            $this->loadLgas();
        }
    }

    public function updatedStateId(): void
    {
        $this->lga_id = '';
        $this->loadLgas();
    }

    public function loadLgas(): void
    {
        if ($this->state_id) {
            $this->lgas = Lga::where('state_id', $this->state_id)->orderBy('name')->get();
        } else {
            $this->lgas = [];
        }
    }

    public function nextStep(): void
    {
        if ($this->validateCurrentStep()) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        $this->currentStep--;
    }

    public function validateCurrentStep(): bool
    {
        switch ($this->currentStep) {
            case 1:
                return $this->validateStep1();
            case 2:
                return $this->validateStep2();
            case 3:
                return $this->validateStep3();
            default:
                return true;
        }
    }

    public function validateStep1(): bool
    {
        $this->validate([
            'full_name' => 'required|string|max:255',
            'family_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'nin' => 'nullable|string|max:11|unique:members,nin,' . $this->member->id,
        ]);

        return true;
    }

    public function validateStep2(): bool
    {
        $this->validate([
            'occupation' => 'required|string|max:255',
            'workplace' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'hometown' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:lgas,id',
            'country' => 'required|string|max:255',
        ]);

        return true;
    }

    public function validateStep3(): bool
    {
        $this->validate([
            'healthcare_provider_id' => 'nullable|exists:healthcare_providers,id',
            'health_status' => 'nullable|string|max:255',
            'contribution_plan_id' => 'required|exists:contribution_plans,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        return true;
    }

    public function save(): void
    {
        if (!$this->validateCurrentStep()) {
            return;
        }

        try {
            // Update member data
            $memberData = [
                'full_name' => $this->full_name,
                'family_name' => $this->family_name,
                'date_of_birth' => $this->date_of_birth,
                'marital_status' => $this->marital_status,
                'nin' => $this->nin,
                'occupation' => $this->occupation,
                'workplace' => $this->workplace,
                'address' => $this->address,
                'hometown' => $this->hometown,
                'state_id' => $this->state_id,
                'lga_id' => $this->lga_id,
                'country' => $this->country,
                'healthcare_provider_id' => $this->healthcare_provider_id,
                'health_status' => $this->health_status,
                'contribution_plan_id' => $this->contribution_plan_id,
                'notes' => $this->notes,
                'is_complete' => true,
            ];

            // Handle photo upload
            if ($this->photo) {
                // Delete old photo if exists
                if ($this->member->photo_path) {
                    Storage::disk('public')->delete($this->member->photo_path);
                }

                $photoPath = $this->photo->store('member-photos', 'public');
                $memberData['photo_path'] = $photoPath;
            }

            // Update member
            $this->member->update($memberData);

            // Update user data if exists
            if ($this->member->user) {
                $this->member->user->update([
                    'phone' => $this->phone,
                    'email' => $this->email,
                ]);
            }

            // Update eligibility status
            $this->member->updateEligibilityStatus();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Member updated successfully.',
            ]);

            $this->redirect(route('members.show', $this->member), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to update member: ' . $e->getMessage(),
            ]);
        }
    }

    public function approve(): void
    {
        if (!auth()->user()->can('approve', $this->member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to approve members.',
            ]);
            return;
        }

        $this->member->approve();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member approved successfully.',
        ]);

        $this->loadMemberData();
    }

    public function suspend(): void
    {
        if (!auth()->user()->can('update', $this->member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to suspend members.',
            ]);
            return;
        }

        $this->member->suspend();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member suspended successfully.',
        ]);

        $this->loadMemberData();
    }

    public function activate(): void
    {
        if (!auth()->user()->can('update', $this->member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to activate members.',
            ]);
            return;
        }

        $this->member->activate();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member activated successfully.',
        ]);

        $this->loadMemberData();
    }

    public function getStepsProperty(): array
    {
        return [
            1 => 'Personal Information',
            2 => 'Address & Location',
            3 => 'Health & Contact',
        ];
    }

    public function getProgressProperty(): int
    {
        return ($this->currentStep / 3) * 100;
    }
}; ?>

<div>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Member</h1>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Update member information - {{ $member->registration_no }}</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                @if($member->status === 'pending' && auth()->user()->can('approve', $member))
                    <flux:button variant="primary" wire:click="approve">
                        Approve Member
                    </flux:button>
                @endif
                
                @if($member->status === 'active' && auth()->user()->can('update', $member))
                    <flux:button variant="outline" wire:click="suspend">
                        Suspend Member
                    </flux:button>
                @endif
                
                @if($member->status === 'suspended' && auth()->user()->can('update', $member))
                    <flux:button variant="primary" wire:click="activate">
                        Activate Member
                    </flux:button>
                @endif
                
                <flux:button variant="outline" href="{{ route('members.show', $member) }}" wire:navigate>
                    View Member
                </flux:button>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Registration Progress</h3>
                <span class="text-sm text-neutral-500 dark:text-neutral-400">Step {{ $currentStep }} of 3</span>
            </div>
            
            <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $this->progress }}%"></div>
            </div>
            
            <div class="flex justify-between mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                @foreach($this->steps as $step => $title)
                    <span class="{{ $currentStep >= $step ? 'text-blue-600 dark:text-blue-400 font-medium' : '' }}">
                        {{ $title }}
                    </span>
                @endforeach
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg">
            <form wire:submit="save" class="space-y-6">
                <!-- Step 1: Personal Information -->
                @if($currentStep === 1)
                    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Personal Information</h3>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Basic personal details of the member</p>
                    </div>
                    
                    <div class="px-6 py-4 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input wire:model="full_name" label="First Name" required />
                            <flux:input wire:model="family_name" label="Family Name" required />
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input wire:model="date_of_birth" type="date" label="Date of Birth" required />
                            <flux:select wire:model="marital_status" label="Marital Status" required>
                                <option value="">Select Status</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="divorced">Divorced</option>
                                <option value="widowed">Widowed</option>
                            </flux:select>
                        </div>
                        
                        <flux:input wire:model="nin" label="National Identification Number (NIN)" placeholder="Optional" />
                    </div>
                @endif

                <!-- Step 2: Address & Location -->
                @if($currentStep === 2)
                    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Address & Location</h3>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Residential and location information</p>
                    </div>
                    
                    <div class="px-6 py-4 space-y-6">
                        <flux:input wire:model="occupation" label="Occupation" required />
                        <flux:input wire:model="workplace" label="Workplace" placeholder="Optional" />
                        <flux:textarea wire:model="address" label="Address" required rows="3" />
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input wire:model="hometown" label="Hometown" required />
                            <flux:select wire:model="country" label="Country" required>
                                <option value="Nigeria">Nigeria</option>
                                <option value="Other">Other</option>
                            </flux:select>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:select wire:model="state_id" label="State" required>
                                <option value="">Select State</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:select wire:model="lga_id" label="Local Government Area" required>
                                <option value="">Select LGA</option>
                                @foreach($lgas as $lga)
                                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                @endif

                <!-- Step 3: Health & Contact -->
                @if($currentStep === 3)
                    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Health & Contact Information</h3>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Health provider and contact details</p>
                    </div>
                    
                    <div class="px-6 py-4 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:select wire:model="healthcare_provider_id" label="Healthcare Provider">
                                <option value="">Select Provider</option>
                                @foreach($healthcareProviders as $provider)
                                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:select wire:model="contribution_plan_id" label="Contribution Plan" required>
                                <option value="">Select Plan</option>
                                @foreach($contributionPlans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} - ₦{{ number_format($plan->amount) }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        
                        <flux:input wire:model="health_status" label="Health Status" placeholder="Optional" />
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input wire:model="phone" label="Phone Number" placeholder="Optional" />
                            <flux:input wire:model="email" type="email" label="Email Address" placeholder="Optional" />
                        </div>
                        
                        <div>
                            <flux:input wire:model="photo" type="file" label="Profile Photo" accept="image/*" />
                            @if($member->photo_path)
                                <div class="mt-2">
                                    <img src="{{ Storage::url($member->photo_path) }}" alt="Current Photo" class="h-20 w-20 rounded-lg object-cover">
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Current photo</p>
                                </div>
                            @endif
                        </div>
                        
                        <flux:textarea wire:model="notes" label="Notes" placeholder="Additional notes about the member" rows="3" />
                    </div>
                @endif

                <!-- Navigation Buttons -->
                <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700 flex justify-between">
                    <div>
                        @if($currentStep > 1)
                            <flux:button type="button" variant="outline" wire:click="previousStep">
                                Previous
                            </flux:button>
                        @endif
                    </div>
                    
                    <div class="flex space-x-3">
                        @if($currentStep < 3)
                            <flux:button type="button" wire:click="nextStep">
                                Next
                            </flux:button>
                        @else
                            <flux:button type="submit" variant="primary">
                                Update Member
                            </flux:button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
