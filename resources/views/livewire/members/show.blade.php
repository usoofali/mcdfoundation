<?php

use App\Models\Member;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Member Details'])] class extends Component {
    public Member $member;
    public string $activeTab = 'info';

    public function mount(Member $member): void
    {
        $this->member = $member->load([
            'state', 
            'lga', 
            'contributionPlan', 
            'healthcareProvider', 
            'creator',
            'dependents'
        ]);
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getStatusColorProperty()
    {
        return match($this->member->status) {
            'pre_registered' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'pending' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'inactive' => 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200',
            'suspended' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'terminated' => 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200',
            default => 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200',
        };
    }

    public function getStatusLabelProperty()
    {
        return match($this->member->status) {
            'pre_registered' => 'Pre-registered',
            'pending' => 'Pending Approval',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'terminated' => 'Terminated',
            default => ucfirst($this->member->status),
        };
    }

    public function getEligibilityStatusProperty()
    {
        if (!$this->member->eligibility_start_date) {
            return [
                'status' => 'not_eligible',
                'label' => 'Not Eligible',
                'color' => 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200',
                'description' => 'Member needs to complete registration and meet contribution requirements.'
            ];
        }

        if ($this->member->eligibility_start_date->isFuture()) {
            return [
                'status' => 'pending_eligibility',
                'label' => 'Pending Eligibility',
                'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'description' => 'Eligible from ' . $this->member->eligibility_start_date->format('M d, Y')
            ];
        }

        return [
            'status' => 'eligible',
            'label' => 'Eligible',
                'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'description' => 'Eligible for health benefits since ' . $this->member->eligibility_start_date->format('M d, Y')
        ];
    }
}; ?>

<div>
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $member->full_name }} {{ $member->family_name }}</h1>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $member->registration_no }}</p>
                </div>
                <div class="mt-4 sm:mt-0 flex space-x-3">
                    <flux:button variant="outline" href="{{ route('members.edit', $member) }}" wire:navigate>
                        Edit Member
                    </flux:button>
                    <flux:button variant="primary" href="{{ route('members.index') }}" wire:navigate>
                        Back to Members
                    </flux:button>
                </div>
            </div>

            <!-- Member Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Status Card -->
                <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400 truncate">Status</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->statusColor }}">
                                                {{ $this->statusLabel }}
                                            </span>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Eligibility Card -->
                <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400 truncate">Health Eligibility</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->eligibilityStatus['color'] }}">
                                                {{ $this->eligibilityStatus['label'] }}
                                            </span>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Age Card -->
                <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400 truncate">Age</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">{{ $member->age }} years</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg">
                <div class="border-b border-neutral-200 dark:border-neutral-700">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button wire:click="setActiveTab('info')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'info' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                            Basic Information
                        </button>
                        <button wire:click="setActiveTab('dependents')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'dependents' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                            Dependents ({{ $member->dependents->count() }})
                        </button>
                        <button wire:click="setActiveTab('contributions')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'contributions' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                            Contributions
                        </button>
                        <button wire:click="setActiveTab('loans')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'loans' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                            Loans
                        </button>
                        <button wire:click="setActiveTab('claims')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'loans' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                            Health Claims
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Basic Information Tab -->
                    @if($activeTab === 'info')
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Personal Information -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Personal Information</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Full Name</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->full_name }} {{ $member->family_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Date of Birth</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->date_of_birth->format('M d, Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Marital Status</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($member->marital_status) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">NIN</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->nin }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Work Information -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Work Information</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Occupation</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->occupation }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Workplace</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->workplace }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Address</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->address }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Location Information -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Location Information</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Hometown</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->hometown }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">State</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->state->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">LGA</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->lga->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Country</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->country }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Health & Plan Information -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Health & Plan Information</h3>
                                <dl class="space-y-3">
                                    @if($member->healthcareProvider)
                                        <div>
                                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Healthcare Provider</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->healthcareProvider->name }}</dd>
                                        </div>
                                    @endif
                                    @if($member->contributionPlan)
                                        <div>
                                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Contribution Plan</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                                {{ $member->contributionPlan?->label }} - ₦{{ number_format($member->contributionPlan->amount) }}
                                            </dd>
                                        </div>
                                    @endif
                                    @if($member->health_status)
                                        <div>
                                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Health Status</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->health_status }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        </div>

                        <!-- Registration Information -->
                        <div class="mt-8 pt-6 border-t border-neutral-200 dark:border-neutral-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Registration Information</h3>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Registration Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->registration_date->format('M d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Registered By</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->creator->name }}</dd>
                                </div>
                                @if($member->eligibility_start_date)
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Eligibility Start Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->eligibility_start_date->format('M d, Y') }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Registration Complete</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $member->is_complete ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                            {{ $member->is_complete ? 'Complete' : 'Incomplete' }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    @endif

                    <!-- Dependents Tab -->
                    @if($activeTab === 'dependents')
                        <livewire:dependents.manage :member="$member" />
                    @endif

                    <!-- Other tabs placeholder -->
                    @if($activeTab === 'contributions')
                        <div class="text-center py-12">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Contributions</h3>
                            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Contribution history will be displayed here.</p>
                        </div>
                    @endif

                    @if($activeTab === 'loans')
                        <div class="text-center py-12">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Loans</h3>
                            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Loan history will be displayed here.</p>
                        </div>
                    @endif

                    @if($activeTab === 'claims')
                        <div class="text-center py-12">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Health Claims</h3>
                            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Health claim history will be displayed here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
</div>
