<?php

use App\Models\Member;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'My Profile'])] class extends Component {
    public Member $member;

    public function mount(): void
    {
        $this->member = auth()->user()->member;
        $this->member->load(['state', 'lga', 'contributionPlan', 'dependents']);
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('My Profile') }}</h2>
        <flux:button :href="route('members.edit', $member)" variant="primary" wire:navigate>
            {{ __('Edit Profile') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Profile Header -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center gap-6">
                <div class="h-24 w-24 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}
                    </span>
                </div>
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $member->full_name }}</h3>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ __('Registration No: :no', ['no' => $member->registration_no]) }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $member->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                            {{ ucfirst($member->status) }}
                        </span>
                        @if($member->is_eligible_for_health)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ __('Health Eligible') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Personal Information') }}</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Full Name') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Date of Birth') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $member->date_of_birth ? $member->date_of_birth->format('M d, Y') : 'N/A' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Gender') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($member->gender ?? 'N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Marital Status') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($member->marital_status ?? 'N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Phone') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->phone ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Email') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->email ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Address Information -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Address Information') }}</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('State') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->state?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('LGA') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->lga?->name ?? 'N/A' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Address') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->address ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Membership Information -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Membership Information') }}</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Registration Date') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $member->registration_date ? $member->registration_date->format('M d, Y') : $member->created_at->format('M d, Y') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Contribution Plan') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $member->contributionPlan?->label ?? 'N/A' }}
                        @if($member->contributionPlan)
                            <span class="text-neutral-500">(₦{{ number_format($member->contributionPlan->amount, 2) }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Eligibility Start Date') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $member->eligibility_start_date ? $member->eligibility_start_date->format('M d, Y') : 'N/A' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Dependents') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $member->dependents->count() }} {{ Str::plural('dependent', $member->dependents->count()) }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Next of Kin -->
        @if($member->next_of_kin_name)
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Next of Kin') }}</h3>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->next_of_kin_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Relationship') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($member->next_of_kin_relationship ?? 'N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->next_of_kin_phone ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Address') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $member->next_of_kin_address ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>
        @endif
    </div>
</div>
