<?php

use App\Models\Member;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'My Profile'])] class extends Component {
    public Member $member;

    public function mount(): void
    {
        $this->member = auth()->user()->member;
        $this->member->load(['state', 'lga', 'contributionPlan', 'dependents', 'healthcareProvider']);
    }

    public function getStatsProperty()
    {
        return [
            'total_contributions' => $this->member->contributions()->where('status', 'paid')->count(),
            'total_amount_paid' => $this->member->contributions()->where('status', 'paid')->sum('amount'),
            'active_loans' => $this->member->loans()->where('status', 'active')->count(),
            'health_claims' => $this->member->healthClaims()->count(),
            'program_enrollments' => $this->member->programEnrollments()->count(),
            'cashout_count' => $this->member->cashout_count ?? 0,
            'membership_duration' => $this->member->created_at->diffInMonths(now()),
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('My Profile') }}</h2>
        <flux:button :href="route('members.edit', $member)" icon="pencil" variant="primary" wire:navigate>
            {{ __('Edit Profile') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <!-- Profile Header -->
        <div
            class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 p-8 shadow-sm">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                <!-- Avatar -->
                <div class="relative">
                    <div class="h-32 w-32 rounded-full bg-blue-100 dark:bg-blue-900/40 border-4 border-blue-200 dark:border-blue-800 flex items-center justify-center shadow-lg overflow-hidden">
                        @if($member->photo_path)
                            <img src="{{ Storage::url($member->photo_path) }}" alt="{{ $member->full_name }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-5xl font-bold text-blue-600 dark:text-blue-400">
                                {{ strtoupper(substr($member->full_name, 0, 1) . substr($member->family_name, 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    @if($member->status === 'active')
                        <div class="absolute bottom-2 right-2 h-6 w-6 bg-green-500 rounded-full border-4 border-white dark:border-neutral-800"></div>
                    @endif
                </div>

                <!-- Member Info -->
                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $member->full_name ." ".$member->family_name }}</h1>
                    <p class="text-neutral-600 dark:text-neutral-400 text-lg mb-3">{{ $member->registration_no }}</p>

                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium
                            {{ $member->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                            <flux:icon name="{{ $member->status === 'active' ? 'check-circle' : 'clock' }}"
                                class="size-4" />
                            {{ ucfirst($member->status) }}
                        </span>

                        @if($member->is_eligible_for_health)
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                <flux:icon name="heart" class="size-4" />
                                {{ __('Health Eligible') }}
                            </span>
                        @endif

                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-neutral-100 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-300">
                            <flux:icon name="calendar" class="size-4" />
                            {{ $this->stats['membership_duration'] }}
                            {{ Str::plural('month', $this->stats['membership_duration']) }}
                        </span>
                    </div>

                    <div
                        class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-sm text-neutral-600 dark:text-neutral-400">
                        @if($member->phone)
                            <div class="flex items-center gap-2">
                                <flux:icon name="phone" class="size-4" />
                                <span>{{ $member->phone }}</span>
                            </div>
                        @endif
                        @if($member->email)
                            <div class="flex items-center gap-2">
                                <flux:icon name="envelope" class="size-4" />
                                <span>{{ $member->email }}</span>
                            </div>
                        @endif
                        @if($member->state)
                            <div class="flex items-center gap-2">
                                <flux:icon name="map-pin" class="size-4" />
                                <span>{{ $member->lga?->name }}, {{ $member->state->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information Column -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Personal Information -->
                <div
                    class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <flux:icon name="user" class="size-5 text-blue-600 dark:text-blue-400" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Personal Information') }}
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('Full Name') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $member->full_name }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('Date of Birth') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $member->date_of_birth ? $member->date_of_birth->format('M d, Y') : 'N/A' }}
                                @if($member->date_of_birth)
                                    <span class="text-xs text-neutral-500">({{ $member->age }} years)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('Gender') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ ucfirst($member->gender ?? 'N/A') }}</dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('Marital Status') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ ucfirst($member->marital_status ?? 'N/A') }}</dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('Phone Number') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $member->phone ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('Email Address') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $member->email ?? 'N/A' }}</dd>
                        </div>
                        @if($member->occupation)
                            <div class="md:col-span-2">
                                <dt
                                    class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                    {{ __('Occupation') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $member->occupation }}
                                </dd>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Address Information -->
                <div
                    class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <flux:icon name="map-pin" class="size-5 text-blue-600 dark:text-blue-400" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Address Information') }}
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('State') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $member->state?->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('LGA') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $member->lga?->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt
                                class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                {{ __('Residential Address') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $member->address ?? 'N/A' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Next of Kin -->
                @if($member->next_of_kin_name)
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                        <div class="flex items-center gap-2 mb-5">
                            <flux:icon name="users" class="size-5 text-blue-600 dark:text-blue-400" />
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Next of Kin') }}</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <dt
                                    class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                    {{ __('Name') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $member->next_of_kin_name }}</dd>
                            </div>
                            <div>
                                <dt
                                    class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                    {{ __('Relationship') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ ucfirst($member->next_of_kin_relationship ?? 'N/A') }}</dd>
                            </div>
                            <div>
                                <dt
                                    class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                    {{ __('Phone Number') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $member->next_of_kin_phone ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt
                                    class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                    {{ __('Address') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $member->next_of_kin_address ?? 'N/A' }}</dd>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar Column -->
            <div class="space-y-6">

                <!-- Membership Details -->
                <div
                    class="bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-950 dark:to-blue-950 rounded-xl border border-indigo-200 dark:border-indigo-800 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <flux:icon name="identification" class="size-5 text-indigo-600 dark:text-indigo-400" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Membership') }}</h3>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <dt
                                class="text-xs font-medium text-indigo-700 dark:text-indigo-300 uppercase tracking-wide">
                                {{ __('Registration Date') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $member->registration_date ? $member->registration_date->format('M d, Y') : $member->created_at->format('M d, Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium text-indigo-700 dark:text-indigo-300 uppercase tracking-wide">
                                {{ __('Contribution Plan') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $member->contributionPlan?->label ?? 'N/A' }}
                            </dd>
                            @if($member->contributionPlan)
                                <dd class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                                    ₦{{ number_format($member->contributionPlan->amount, 2) }}<span
                                        class="text-xs font-normal">/{{ $member->contributionPlan->frequency }}</span>
                                </dd>
                            @endif
                        </div>
                        @if($member->eligibility_start_date)
                            <div>
                                <dt
                                    class="text-xs font-medium text-indigo-700 dark:text-indigo-300 uppercase tracking-wide">
                                    {{ __('Eligibility Start') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $member->eligibility_start_date->format('M d, Y') }}
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt
                                class="text-xs font-medium text-indigo-700 dark:text-indigo-300 uppercase tracking-wide">
                                {{ __('Cashout Count') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $this->stats['cashout_count'] }}
                                {{ Str::plural('time', $this->stats['cashout_count']) }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Dependents -->
                <div
                    class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <flux:icon name="users" class="size-5 text-blue-600 dark:text-blue-400" />
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Dependents') }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $member->dependents->count() }}
                            </span>
                            <flux:button size="sm" href="{{ route('dependents.manage', $member) }}" wire:navigate>
                                {{ __('Manage') }}
                            </flux:button>
                        </div>
                    </div>

                    @if($member->dependents->count() > 0)
                        <div class="space-y-3">
                            @foreach($member->dependents as $dependent)
                                <div
                                    class="flex items-start gap-3 p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                    <div
                                        class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                            {{ strtoupper(substr($dependent->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $dependent->name }}
                                        </p>
                                        <div
                                            class="flex flex-wrap items-center gap-3 mt-1 text-xs text-neutral-600 dark:text-neutral-400">
                                            <span class="inline-flex items-center gap-1">
                                                <flux:icon name="user" class="size-3" />
                                                {{ ucfirst($dependent->relationship) }}
                                            </span>
                                            @if($dependent->date_of_birth)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon name="cake" class="size-3" />
                                                    {{ $dependent->date_of_birth->format('M d, Y') }}
                                                    ({{ $dependent->date_of_birth->age }} yrs)
                                                </span>
                                            @endif
                                            @if($dependent->gender)
                                                <span class="inline-flex items-center gap-1">
                                                    {{ ucfirst($dependent->gender) }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($dependent->phone)
                                            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                                                <flux:icon name="phone" class="size-3 inline" /> {{ $dependent->phone }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <flux:icon name="users" class="size-12 text-neutral-300 dark:text-neutral-600 mx-auto mb-3" />
                            <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-3">
                                {{ __('No dependents added yet') }}</p>
                            <flux:button size="sm" href="{{ route('dependents.manage', $member) }}" wire:navigate>
                                {{ __('Add Dependent') }}
                            </flux:button>
                        </div>
                    @endif
                </div>

                <!-- Healthcare Provider -->
                @if($member->healthcareProvider)
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <flux:icon name="building-office-2" class="size-5 text-blue-600 dark:text-blue-400" />
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Healthcare Provider') }}
                            </h3>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <dt
                                    class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                    {{ __('Provider Name') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $member->healthcareProvider->name }}</dd>
                            </div>
                            @if($member->healthcareProvider->address)
                                <div>
                                    <dt
                                        class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wide">
                                        {{ __('Address') }}</dt>
                                    <dd class="mt-1 text-xs text-gray-900 dark:text-white">
                                        {{ $member->healthcareProvider->address }}</dd>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>