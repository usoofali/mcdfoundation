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
            'dependents',
            'programEnrollments.program',
            'contributions' => function ($query) {
                $query->orderBy('payment_date', 'desc')->limit(50);
            },
            'loans' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            },
            'healthClaims' => function ($query) {
                $query->with('healthcareProvider')->orderBy('claim_date', 'desc')->limit(20);
            }
        ]);
    }

    public function approveMember(): void
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
    }

    public function rejectMember(): void
    {
        if (!auth()->user()->can('update', $this->member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to reject members.',
            ]);
            return;
        }
        $this->member->update(['status' => 'inactive']);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member rejected successfully.',
        ]);
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getStatusColorProperty()
    {
        return match ($this->member->status) {
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
        return match ($this->member->status) {
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
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $member->full_name }}
                    {{ $member->family_name }}
                </h1>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $member->registration_no }}</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <flux:button variant="outline" href="{{ route('members.edit', $member) }}" wire:navigate>
                    Edit Member
                </flux:button>
                <flux:button variant="primary" href="{{ route('members.index') }}" wire:navigate>
                    Back to Members
                </flux:button>
                @if($member->status === 'pending')
                    <flux:button variant="primary" wire:click="approveMember" class="ml-2">
                        Approve
                    </flux:button>
                    <flux:button variant="danger" wire:click="rejectMember" class="ml-2">
                        Reject
                    </flux:button>
                @endif
            </div>
        </div>

        <!-- Member Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Status Card -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400 truncate">Status
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->statusColor }}">
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
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400 truncate">Health
                                    Eligibility</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->eligibilityStatus['color'] }}">
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
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
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
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'claims' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                        Health Claims
                    </button>
                    <button wire:click="setActiveTab('programs')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'programs' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                        Programs ({{ $member->programEnrollments->count() }})
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
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->full_name }}
                                        {{ $member->family_name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Date of Birth
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        {{ $member->date_of_birth->format('M d, Y') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Marital Status
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        {{ ucfirst($member->marital_status) }}
                                    </dd>
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
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Health & Plan Information
                            </h3>
                            <dl class="space-y-3">
                                @if($member->healthcareProvider)
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Healthcare
                                            Provider</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ $member->healthcareProvider->name }}
                                        </dd>
                                    </div>
                                @endif
                                @if($member->contributionPlan)
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Contribution Plan
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ $member->contributionPlan?->label }} -
                                            ₦{{ number_format($member->contributionPlan->amount) }}
                                        </dd>
                                    </div>
                                @endif
                                @if($member->health_status)
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Health Status
                                        </dt>
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
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Registration Date
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ $member->registration_date->format('M d, Y') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Registered By</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->creator->name }}</dd>
                            </div>
                            @if($member->eligibility_start_date)
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Eligibility Start
                                        Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        {{ $member->eligibility_start_date->format('M d, Y') }}
                                    </dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Registration Complete
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $member->is_complete ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
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

                <!-- Contributions Tab -->
                @if($activeTab === 'contributions')
                    <div class="space-y-6">
                        <!-- Contribution Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                                    ₦{{ number_format($member->total_contributions, 2) }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Contributions</div>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                                    {{ $member->contributions->count() }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Payments</div>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                                    ₦{{ number_format($member->total_fines, 2) }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Fines</div>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ $member->contributions->where('status', 'verified')->count() }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Verified Payments</div>
                            </div>
                        </div>

                        <!-- Contribution History -->
                        @if($member->contributions->count() > 0)
                            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Payment History</h3>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Recent contributions (last 50)</p>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                    Date
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                    Period
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                    Amount
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                    Fine
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                    Method
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                    Receipt
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                                            @foreach($member->contributions as $contribution)
                                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900 dark:text-white">
                                                        {{ $contribution->payment_date->format('M d, Y') }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                        @if($contribution->period_start && $contribution->period_end)
                                                            {{ $contribution->period_start->format('M Y') }} - {{ $contribution->period_end->format('M Y') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-neutral-900 dark:text-white">
                                                        ₦{{ number_format($contribution->amount, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                        @if($contribution->fine_amount > 0)
                                                            <span class="text-red-600 dark:text-red-400">₦{{ number_format($contribution->fine_amount, 2) }}</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                        {{ ucfirst($contribution->payment_method) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                            @if($contribution->status === 'verified') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @elseif($contribution->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @elseif($contribution->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                            @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                            @endif">
                                                            {{ ucfirst($contribution->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                        @if($contribution->receipt_number)
                                                            <span class="font-mono text-xs">{{ $contribution->receipt_number }}</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Contributions Yet</h3>
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">This member has not made any contributions yet.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Loans Tab -->
                @if($activeTab === 'loans')
                    <div class="space-y-6">
                        <!-- Loan Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                                    {{ $member->loans->count() }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Loans</div>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $member->loans->where('status', 'active')->count() }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Active Loans</div>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ $member->loans->where('status', 'completed')->count() }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Completed</div>
                            </div>
                        </div>

                        <!-- Loan History -->
                        @if($member->loans->count() > 0)
                            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Loan History</h3>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Recent loans (last 20)</p>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Type</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Amount</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Repayment</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Start Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                                            @foreach($member->loans as $loan)
                                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                                    <td class="px-6 py-4">
                                                        <div class="text-sm font-medium text-neutral-900 dark:text-white">{{ ucfirst($loan->loan_type) }}</div>
                                                        @if($loan->item_description)
                                                            <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ Str::limit($loan->item_description, 40) }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-neutral-900 dark:text-white">
                                                        ₦{{ number_format($loan->amount, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                        {{ ucfirst($loan->repayment_mode) }}<br>
                                                        <span class="text-xs">₦{{ number_format($loan->installment_amount, 2) }}/{{ $loan->repayment_period }}</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                        {{ $loan->start_date ? $loan->start_date->format('M d, Y') : '-' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                            @if($loan->status === 'active') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                            @elseif($loan->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @elseif($loan->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @elseif($loan->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                            @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                            @endif">
                                                            {{ ucfirst($loan->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Loans Yet</h3>
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">This member has not taken any loans yet.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Health Claims Tab -->
                @if($activeTab === 'claims')
                    <div class="space-y-6">
                        <!-- Claim Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                                    {{ $member->healthClaims->count() }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Claims</div>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ $member->healthClaims->where('status', 'submitted')->count() }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Pending</div>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ $member->healthClaims->where('status', 'paid')->count() }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Paid</div>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                                    ₦{{ number_format($member->healthClaims->sum('covered_amount'), 2) }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Covered</div>
                            </div>
                        </div>

                        <!-- Claims History -->
                        @if($member->healthClaims->count() > 0)
                            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                                <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Claims History</h3>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Recent health claims (last 20)</p>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Claim #</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Type</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Provider</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Billed</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Covered</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                                            @foreach($member->healthClaims as $claim)
                                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-neutral-900 dark:text-white">
                                                        {{ $claim->claim_number }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900 dark:text-white">
                                                        {{ $claim->claim_type_label }}
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                                                        {{ $claim->healthcareProvider->name ?? '-' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900 dark:text-white">
                                                        ₦{{ number_format($claim->billed_amount, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                                        ₦{{ number_format($claim->covered_amount, 2) }}
                                                        <span class="text-xs text-neutral-500">({{ $claim->coverage_percent }}%)</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                        {{ $claim->claim_date->format('M d, Y') }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                            @if($claim->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @elseif($claim->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                            @elseif($claim->status === 'submitted') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @elseif($claim->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                            @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                            @endif">
                                                            {{ $claim->status_label }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Health Claims Yet</h3>
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">This member has not submitted any health claims yet.</p>
                            </div>
                        @endif
                    </div>
                @endif

                @if($activeTab === 'programs')
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Program Enrollments</h3>
                        @if($member->programEnrollments->count() > 0)
                            <div class="space-y-4">
                                @foreach($member->programEnrollments as $enrollment)
                                    <div
                                        class="border border-neutral-200 dark:border-neutral-700 rounded-lg p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                                    {{ $enrollment->program->title }}
                                                </h4>
                                                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                                    {{ $enrollment->program->description }}
                                                </p>
                                                <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-neutral-500 dark:text-neutral-400">Enrolled:</span>
                                                        <span
                                                            class="ml-1 text-gray-900 dark:text-white">{{ $enrollment->enrolled_at->format('M d, Y') }}</span>
                                                    </div>
                                                    @if($enrollment->completed_at)
                                                        <div>
                                                            <span class="text-neutral-500 dark:text-neutral-400">Completed:</span>
                                                            <span
                                                                class="ml-1 text-gray-900 dark:text-white">{{ $enrollment->completed_at->format('M d, Y') }}</span>
                                                        </div>
                                                    @endif
                                                    @if($enrollment->certificate_issued_at)
                                                        <div>
                                                            <span class="text-neutral-500 dark:text-neutral-400">Certificate:</span>
                                                            <span class="ml-1 text-green-600 dark:text-green-400">Issued</span>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <span class="text-neutral-500 dark:text-neutral-400">Duration:</span>
                                                        <span
                                                            class="ml-1 text-gray-900 dark:text-white">{{ $enrollment->program->duration_weeks }}
                                                            weeks</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                                @if($enrollment->status === 'enrolled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                                @elseif($enrollment->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                                @elseif($enrollment->status === 'dropped') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                                @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                                @endif">
                                                    {{ ucfirst($enrollment->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Program Enrollments</h3>
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">This member has not enrolled in
                                    any vocational programs yet.</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>