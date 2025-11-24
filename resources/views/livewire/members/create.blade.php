<?php

use App\Models\ContributionPlan;
use App\Models\HealthcareProvider;
use App\Models\Lga;
use App\Models\Member;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app', ['title' => 'Register Member'])] class extends Component
{
    use WithFileUploads;

    // Form data
    public string $full_name = '';

    public string $family_name = '';

    public string $date_of_birth = '';

    public string $marital_status = '';

    public string $gender = '';

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

    public string $password = '';

    public $photo;

    public string $notes = '';

    // Form state
    public int $currentStep = 1;

    public bool $isPreRegistration = true;

    public bool $isComplete = false;

    // Options
    public $states;

    public $lgas = [];

    public $healthcareProviders;

    public $contributionPlans;

    public function mount(): void
    {
        $this->states = State::orderBy('name')->get();
        $this->healthcareProviders = HealthcareProvider::active()->orderBy('name')->get();
        $this->contributionPlans = ContributionPlan::active()->orderBy('amount')->get();
    }

    public function updatedStateId(): void
    {
        $this->lga_id = '';
        $this->lgas = Lga::where('state_id', $this->state_id)->orderBy('name')->get();
    }

    public function nextStep(): void
    {
        $this->validateStep();
        $this->currentStep++;
    }

    public function previousStep(): void
    {
        $this->currentStep--;
    }

    public function toggleRegistrationType(): void
    {
        $this->isPreRegistration = ! $this->isPreRegistration;
        $this->currentStep = 1;
    }

    public function validateStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'full_name' => 'required|string|max:150',
                'family_name' => 'required|string|max:150',
                'date_of_birth' => 'required|date|before:today',
                'marital_status' => 'required|in:single,married,divorced',
                'nin' => 'required|string|size:11|unique:members,nin',
            ]);
        } elseif ($this->currentStep === 2) {
            $this->validate([
                'occupation' => 'required|string|max:150',
                'workplace' => 'required|string|max:200',
                'address' => 'required|string',
                'hometown' => 'required|string|max:100',
                'state_id' => 'required|exists:states,id',
                'lga_id' => 'required|exists:lgas,id',
            ]);
        } elseif ($this->currentStep === 3) {
            $this->validate([
                'healthcare_provider_id' => 'nullable|exists:healthcare_providers,id',
                'health_status' => 'nullable|string',
                'contribution_plan_id' => 'nullable|exists:contribution_plans,id',
                'phone' => 'nullable|string|max:20',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => ['required', 'string', Password::default()],
                'photo' => 'nullable|image|max:2048|mimes:jpg,jpeg,png',
                'notes' => 'nullable|string',
            ]);
        }
    }

    public function save(): void
    {
        $this->validateStep();

        // Get member role
        $memberRole = Role::where('name', 'member')->first();

        if (! $memberRole) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Member role not found. Please contact administrator.',
            ]);

            return;
        }

        $member = DB::transaction(function () use ($memberRole) {
            // Create User first
            $user = User::create([
                'name' => $this->full_name.' '.$this->family_name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role_id' => $memberRole->id,
                'phone' => $this->phone ?: null,
                'address' => $this->address,
                'state_id' => $this->state_id ? (int) $this->state_id : null,
                'lga_id' => $this->lga_id ? (int) $this->lga_id : null,
            ]);

            // Prepare member data
            $memberData = [
                'user_id' => $user->id,
                'full_name' => $this->full_name,
                'family_name' => $this->family_name,
                'date_of_birth' => $this->date_of_birth,
                'marital_status' => $this->marital_status,
                'nin' => $this->nin,
                'occupation' => $this->occupation,
                'workplace' => $this->workplace,
                'address' => $this->address,
                'hometown' => $this->hometown,
                'state_id' => $this->state_id ? (int) $this->state_id : null,
                'lga_id' => $this->lga_id ? (int) $this->lga_id : null,
                'country' => $this->country,
                'healthcare_provider_id' => $this->healthcare_provider_id ? (int) $this->healthcare_provider_id : null,
                'health_status' => $this->health_status,
                'contribution_plan_id' => $this->contribution_plan_id ? (int) $this->contribution_plan_id : null,
                'registration_date' => now()->toDateString(),
                'created_by' => Auth::id(),
                'is_complete' => ! $this->isPreRegistration,
                'status' => $this->isPreRegistration ? 'pre_registered' : 'pending',
                'notes' => $this->notes,
            ];

            // Handle photo upload
            if ($this->photo) {
                $memberData['photo_path'] = $this->photo->store('member-photos', 'public');
            }

            // Create Member linked to User
            return Member::create($memberData);
        });

        if ($this->isPreRegistration) {
            session()->flash('success', 'Member pre-registered successfully with user account. Complete registration later.');
        } else {
            session()->flash('success', 'Member registered successfully with user account. Awaiting approval.');
        }

        $this->redirect(route('members.show', $member));
    }

    public function getMaritalStatusOptionsProperty()
    {
        return [
            'single' => 'Single',
            'married' => 'Married',
            'divorced' => 'Divorced',
        ];
    }

    public function getGenderOptionsProperty(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
            'prefer_not_to_say' => 'Prefer not to say',
        ];
    }
}; ?>

<div>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Register Member</h1>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Add a new member to the system</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <flux:button variant="outline" wire:click="toggleRegistrationType">
                        @if($isPreRegistration)
                            Switch to Full Registration
                        @else
                            Switch to Pre-registration
                        @endif
                    </flux:button>
                </div>
            </div>

            <!-- Registration Type Info -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                            @if($isPreRegistration)
                                Pre-registration Mode
                            @else
                                Full Registration Mode
                            @endif
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            @if($isPreRegistration)
                                Collect basic information now and complete registration later. Only name, date of birth, marital status, and NIN are required.
                            @else
                                Complete all member information in one go. All fields are required for full registration.
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between">
                    @foreach([1, 2, 3] as $step)
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep >= $step ? 'bg-indigo-600 text-white' : 'bg-neutral-200 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300' }}">
                                {{ $step }}
                            </div>
                            <div class="ml-2 text-sm font-medium {{ $currentStep >= $step ? 'text-indigo-600 dark:text-indigo-400' : 'text-neutral-500 dark:text-neutral-400' }}">
                                @if($step === 1)
                                    Basic Info
                                @elseif($step === 2)
                                    Location & Work
                                @else
                                    Health & Contact
                                @endif
                            </div>
                            @if($step < 3)
                                <div class="w-16 h-0.5 mx-4 {{ $currentStep > $step ? 'bg-indigo-600' : 'bg-neutral-200 dark:bg-neutral-700' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Form -->
            <form wire:submit="save" class="space-y-6">
                <!-- Step 1: Basic Information -->
                @if($currentStep === 1)
                    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Basic Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input 
                                wire:model="full_name" 
                                label="First Name" 
                                required 
                                autofocus
                            />
                            
                            <flux:input 
                                wire:model="family_name" 
                                label="Family Name" 
                                required
                            />
                            
                            <flux:input
                                wire:model="date_of_birth" 
                                type="date" 
                                label="Date of Birth" 
                                required
                            />
                            
                            <flux:select
                                wire:model.live="marital_status"
                                label="Marital Status"
                                placeholder="Select marital status"
                                required
                            >
                                @foreach($this->maritalStatusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:select
                                wire:model="gender"
                                label="Gender"
                                placeholder="Select gender (optional)"
                            >
                                <option value="">Prefer not to say</option>
                                @foreach($this->genderOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:input 
                                wire:model="nin" 
                                label="National ID Number (NIN)" 
                                placeholder="11-digit NIN"
                                required
                                class="md:col-span-2"
                            />
                        </div>
                    </div>
                @endif

                <!-- Step 2: Location & Work -->
                @if($currentStep === 2)
                    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Location & Work Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input 
                                wire:model="occupation" 
                                label="Occupation" 
                                required
                            />
                            
                            <flux:input 
                                wire:model="workplace" 
                                label="Place of Work" 
                                required
                            />
                            
                            <flux:input 
                                wire:model="hometown" 
                                label="Hometown" 
                                required
                            />
                            
                            <flux:select
                                wire:model.live="state_id"
                                label="State of Origin"
                                placeholder="Select state"
                                required
                            >
                                @foreach($this->states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:select
                                wire:model="lga_id"
                                label="Local Government Area"
                                placeholder="{{ $state_id ? 'Select LGA' : 'Select a state first' }}"
                                :disabled="empty($state_id)"
                                required
                            >
                                @foreach($this->lgas as $lga)
                                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:textarea 
                                wire:model="address" 
                                label="Full Address" 
                                required
                                class="md:col-span-2"
                            />
                        </div>
                    </div>
                @endif

                <!-- Step 3: Health & Contact -->
                @if($currentStep === 3)
                    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Health & Contact Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:select
                                wire:model="healthcare_provider_id"
                                label="Preferred Healthcare Provider"
                                placeholder="Select provider (optional)"
                            >
                                <option value="">No preference</option>
                                @foreach($this->healthcareProviders as $provider)
                                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:select
                                wire:model="contribution_plan_id"
                                label="Contribution Plan"
                                placeholder="Select plan (optional)"
                            >
                                <option value="">No plan selected</option>
                                @foreach($this->contributionPlans as $plan)
                                    <option value="{{ $plan->id }}">
                                        {{ $plan->label }} - ₦{{ number_format($plan->amount) }} ({{ ucfirst($plan->frequency) }})
                                    </option>
                                @endforeach
                            </flux:select>
                            
                            <flux:input 
                                wire:model="phone" 
                                label="Phone Number"
                                placeholder="+234..."
                            />
                            
                            <flux:input 
                                wire:model="email" 
                                type="email" 
                                label="Email Address"
                                required
                            />
                            
                            <flux:input 
                                wire:model="password" 
                                type="password" 
                                label="Password"
                                required
                            />
                            
                            <flux:input 
                                wire:model="photo" 
                                type="file"
                                label="Profile Photo"
                                accept="image/*"
                            />
                            
                            <flux:textarea 
                                wire:model="health_status" 
                                label="Health Status/Medical Notes"
                                class="md:col-span-2"
                            />
                            
                            <flux:textarea 
                                wire:model="notes" 
                                label="Additional Notes"
                                class="md:col-span-2"
                            />
                        </div>
                    </div>
                @endif

                <!-- Form Actions -->
                <div class="flex justify-between">
                    <div>
                        @if($currentStep > 1)
                            <flux:button type="button" variant="outline" wire:click="previousStep">
                                Previous
                            </flux:button>
                        @endif
</div>

                    <div class="flex space-x-3">
                        @if($currentStep < 3)
                            <flux:button type="button" variant="primary" wire:click="nextStep">
                                Next
                            </flux:button>
                        @else
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                <span wire:loading.remove>
                                    @if($isPreRegistration)
                                        Pre-register Member
                                    @else
                                        Register Member
                                    @endif
                                </span>
                                <span wire:loading>Processing...</span>
                            </flux:button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
</div>
