<?php

use App\Models\Member;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'My Dashboard'])] class extends Component {
    public Member $member;

    public function mount(): void
    {
        $this->member = auth()->user()->member;

        // Load relationships
        $this->member->load([
            'contributions' => fn($q) => $q->latest()->limit(5),
            'loans' => fn($q) => $q->latest()->limit(3),
            'healthClaims' => fn($q) => $q->latest()->limit(3),
            'dependents',
        ]);
    }

    public function getStatsProperty()
    {
        // Calculate outstanding balance from loans
        $outstandingBalance = $this->member->loans()
            ->whereIn('status', ['disbursed', 'defaulted'])
            ->get()
            ->sum(function ($loan) {
                return $loan->outstanding_balance;
            });

        return [
            'total_contributions' => $this->member->contributions()->where('status', 'paid')->sum('amount'),
            'pending_contributions' => $this->member->contributions()->where('status', 'pending')->count(),
            'active_loans' => $this->member->loans()->whereIn('status', ['approved', 'disbursed'])->count(),
            'outstanding_balance' => $outstandingBalance,
            'total_claims' => $this->member->healthClaims()->count(),
            'pending_claims' => $this->member->healthClaims()->where('status', 'submitted')->count(),
            'dependents_count' => $this->member->dependents()->count(),
            'is_eligible' => $this->member->checkHealthEligibility('outpatient')['eligible'] ?? false,
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('My Dashboard') }}</h2>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <h3 class="text-2xl font-bold">{{ __('Welcome back, :name!', ['name' => $member->first_name]) }}</h3>
            <p class="mt-2 text-blue-100">{{ __('Registration No: :no', ['no' => $member->registration_no]) }}</p>
            <div class="mt-4 flex items-center gap-4">
                <div>
                    <span class="text-sm text-blue-100">{{ __('Member Since') }}</span>
                    <p class="text-lg font-semibold">{{ $member->created_at->format('M Y') }}</p>
                </div>
                <div class="ml-auto">
                    @if($this->stats['is_eligible'])
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            ✓ {{ __('Health Eligible') }}
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            {{ __('Not Eligible Yet') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Contributions -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Contributions') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            ₦{{ number_format($this->stats['total_contributions'], 2) }}
                        </p>
                    </div>
                    <div
                        class="h-12 w-12 bg-green-100 dark:bg-green-900/40 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                @if($this->stats['pending_contributions'] > 0)
                    <p class="mt-2 text-xs text-yellow-600 dark:text-yellow-400">
                        {{ $this->stats['pending_contributions'] }} {{ __('pending verification') }}
                    </p>
                @endif
            </div>

            <!-- Active Loans -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Active Loans') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $this->stats['active_loans'] }}
                        </p>
                    </div>
                    <div
                        class="h-12 w-12 bg-blue-100 dark:bg-blue-900/40 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                @if($this->stats['outstanding_balance'] > 0)
                    <p class="mt-2 text-xs text-neutral-600 dark:text-neutral-400">
                        {{ __('Outstanding: ₦:amount', ['amount' => number_format($this->stats['outstanding_balance'], 2)]) }}
                    </p>
                @endif
            </div>

            <!-- Health Claims -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Health Claims') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $this->stats['total_claims'] }}
                        </p>
                    </div>
                    <div class="h-12 w-12 bg-red-100 dark:bg-red-900/40 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                </div>
                @if($this->stats['pending_claims'] > 0)
                    <p class="mt-2 text-xs text-yellow-600 dark:text-yellow-400">
                        {{ $this->stats['pending_claims'] }} {{ __('pending approval') }}
                    </p>
                @endif
            </div>

            <!-- Dependents -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Dependents') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $this->stats['dependents_count'] }}
                        </p>
                    </div>
                    <div
                        class="h-12 w-12 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <flux:button size="xs" :href="route('my.dependents')" wire:navigate class="mt-2">
                    {{ __('Manage') }}
                </flux:button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Quick Actions') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <flux:button :href="route('my.contributions.submit')" variant="outline" wire:navigate
                    class="flex flex-col items-center gap-2 py-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm">{{ __('Submit Payment') }}</span>
                </flux:button>

                <flux:button :href="route('my.loans.apply')" variant="outline" wire:navigate
                    class="flex flex-col items-center gap-2 py-4">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm">{{ __('Apply for Loan') }}</span>
                </flux:button>

                <flux:button :href="route('my.claims.submit')" variant="outline" wire:navigate
                    class="flex flex-col items-center gap-2 py-4">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span class="text-sm">{{ __('Submit Claim') }}</span>
                </flux:button>

                <flux:button :href="route('my.programs.browse')" variant="outline" wire:navigate
                    class="flex flex-col items-center gap-2 py-4">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="text-sm">{{ __('Browse Programs') }}</span>
                </flux:button>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Contributions -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Recent Contributions') }}
                    </h3>
                    <flux:button size="xs" :href="route('my.contributions')" wire:navigate>
                        {{ __('View All') }}
                    </flux:button>
                </div>
                @if($member->contributions->count() > 0)
                    <div class="space-y-3">
                        @foreach($member->contributions as $contribution)
                            <div
                                class="flex items-center justify-between py-2 border-b border-neutral-100 dark:border-neutral-700 last:border-0">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        ₦{{ number_format($contribution->amount, 2) }}
                                    </p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $contribution->payment_date->format('M d, Y') }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                            {{ $contribution->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                    {{ ucfirst($contribution->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No contributions yet') }}</p>
                @endif
            </div>

            <!-- Recent Loans -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Recent Loans') }}</h3>
                    <flux:button size="xs" :href="route('my.loans')" wire:navigate>
                        {{ __('View All') }}
                    </flux:button>
                </div>
                @if($member->loans->count() > 0)
                    <div class="space-y-3">
                        @foreach($member->loans as $loan)
                                    <div
                                        class="flex items-center justify-between py-2 border-b border-neutral-100 dark:border-neutral-700 last:border-0">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                ₦{{ number_format($loan->amount, 2) }}
                                            </p>
                                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                                {{ $loan->loan_type_label }}
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                                                    {{ $loan->status === 'disbursed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                            ($loan->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                            {{ $loan->status_label }}
                                        </span>
                                    </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No loans yet') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>