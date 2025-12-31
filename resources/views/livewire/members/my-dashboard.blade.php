<?php

use App\Models\Member;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'My Dashboard'])] class extends Component {
    public Member $member;

    public function mount(): void
    {
        $this->member = auth()->user()->member;

        // Ensure member has future contributions (on-demand check)
        app(\App\Services\ExpectedContributionService::class)->ensureFutureContributions($this->member);

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

        // Get expected contributions data
        $nextDue = $this->member->expectedContributions()
            ->unpaid()
            ->notAwaitingVerification()
            ->orderBy('due_date')
            ->first();

        $overdueContributions = $this->member->expectedContributions()
            ->overdue()
            ->notAwaitingVerification();

        $awaitingVerification = $this->member->expectedContributions()
            ->awaitingVerification();

        return [
            // Existing stats
            'total_contributions' => $this->member->contributions()->where('status', 'paid')->sum('amount'),
            'pending_contributions' => $this->member->contributions()->where('status', 'pending')->count(),
            'active_loans' => $this->member->loans()->whereIn('status', ['approved', 'disbursed'])->count(),
            'outstanding_balance' => $outstandingBalance,
            'total_claims' => $this->member->healthClaims()->count(),
            'pending_claims' => $this->member->healthClaims()->where('status', 'submitted')->count(),
            'dependents_count' => $this->member->dependents()->count(),
            'is_eligible' => $this->member->checkHealthEligibility('outpatient')['eligible'] ?? false,

            // Expected contributions stats
            'next_payment' => $nextDue,
            'next_payment_amount' => $nextDue?->expected_amount ?? 0,
            'next_payment_due' => $nextDue?->due_date,
            'next_payment_overdue' => $nextDue?->status === 'overdue',
            'overdue_count' => $overdueContributions->count(),
            'overdue_amount' => $overdueContributions->sum('expected_amount'),
            'overdue_fines' => $overdueContributions->sum('fine_amount'),
            'total_overdue' => $overdueContributions->sum('expected_amount') + $overdueContributions->sum('fine_amount'),
            'awaiting_verification_count' => $awaitingVerification->count(),

            // Cashout info
            'cashout_eligible_amount' => $this->member->cashout_eligible_amount,
            'cashout_count' => $this->member->cashout_count,

            // Contribution plan
            'contribution_plan' => $this->member->contributionPlan,

            // Loan eligibility
            'loan_eligible' => $this->member->status === 'active' && $this->member->contributions()->where('status', 'paid')->count() >= 6,

            // Program enrollments
            'program_enrollments' => $this->member->programEnrollments()->whereIn('status', ['enrolled', 'completed'])->count(),
        ];
    }

    public function getUpcomingPaymentsProperty()
    {
        return $this->member->expectedContributions()
            ->unpaid()
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    public function requestReactivation(): void
    {
        try {
            $this->member->requestReactivation();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Reactivation request submitted successfully. Staff will review your request shortly.',
            ]);

            $this->redirect(route('dashboard'));
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
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
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <flux:heading size="xl" class="text-gray-900 dark:text-white">
                        Welcome back, {{ $member->full_name . ' ' . $member->family_name }}!
                    </flux:heading>
                    <div class="mt-3 flex flex-wrap items-center gap-4 sm:gap-6">
                        <div>
                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ __('Registration No') }}
                            </flux:text>
                            <flux:text class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $member->registration_no }}
                            </flux:text>
                        </div>
                        <div class="h-8 w-px bg-neutral-200 dark:bg-neutral-700 hidden sm:block"></div>
                        <div>
                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ __('Member Since') }}
                            </flux:text>
                            <flux:text class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $member->created_at->format('M Y') }}
                            </flux:text>
                        </div>
                        @if($this->stats['contribution_plan'])
                            <div class="h-8 w-px bg-neutral-200 dark:bg-neutral-700 hidden sm:block"></div>
                            <div>
                                <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ __('Plan') }}
                                </flux:text>
                                <flux:text class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $this->stats['contribution_plan']->name }}
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    {{-- Health Eligibility Status --}}
                    @if($this->stats['is_eligible'])
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <flux:icon name="heart" class="size-4" />
                            {{ __('Health Eligible') }}
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            <flux:icon name="clock" class="size-4" />
                            {{ __('Not Eligible') }}
                        </span>
                    @endif

                    {{-- Loan Eligibility Status --}}
                    @if($this->stats['loan_eligible'])
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            <flux:icon name="currency-dollar" class="size-4" />
                            {{ __('Loan Eligible') }}
                        </span>
                    @endif

                    {{-- Program Enrollment Status --}}
                    @if($this->stats['program_enrollments'] > 0)
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                            <flux:icon name="academic-cap" class="size-4" />
                            {{ $this->stats['program_enrollments'] }}
                            {{ Str::plural('Program', $this->stats['program_enrollments']) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Account Suspended Banner -->
        @if($member->status === 'suspended')
            <div class="rounded-xl border border-orange-200 bg-orange-50 p-6 dark:border-orange-800 dark:bg-orange-950">
                <div class="flex items-start gap-4">
                    <flux:icon name="exclamation-triangle" class="size-8 text-orange-600 dark:text-orange-400" />
                    <div class="flex-1">
                        <flux:heading size="lg" class="text-orange-900 dark:text-orange-100">
                            Account Suspended After Cashout
                        </flux:heading>
                        <flux:text class="mt-2 text-orange-800 dark:text-orange-200">
                            Your account has been suspended following your recent cashout (Cycle
                            #{{ $member->cashout_count }}).
                            To continue your membership and start a new cycle, please request reactivation below.
                        </flux:text>
                        <div class="mt-4">
                            <flux:button wire:click="requestReactivation" variant="primary" icon="arrow-path">
                                Request Reactivation
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Reactivation Pending Banner -->
        @if($member->status === 'pending' && $member->last_cashout_date)
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
                <div class="flex items-start gap-4">
                    <flux:icon name="clock" class="size-8 text-blue-600 dark:text-blue-400" />
                    <div class="flex-1">
                        <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                            Reactivation Pending Approval
                        </flux:heading>
                        <flux:text class="mt-2 text-blue-800 dark:text-blue-200">
                            Your reactivation request has been submitted and is awaiting staff approval.
                            You will be notified once your account is reactivated and you can resume making contributions.
                        </flux:text>
                        <flux:text class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            Last cashout: {{ $member->last_cashout_date->format('M d, Y') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        @endif

        <!-- Awaiting Verification Banner -->
        @if($this->stats['awaiting_verification_count'] > 0 && $member->status === 'active')
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
                <div class="flex items-start gap-4">
                    <flux:icon name="clock" class="size-8 text-blue-600 dark:text-blue-400" />
                    <div class="flex-1">
                        <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                            {{ $this->stats['awaiting_verification_count'] }} Payment {{ Str::plural('is', $this->stats['awaiting_verification_count']) }} Awaiting Verification
                        </flux:heading>
                        <flux:text class="mt-2 text-blue-800 dark:text-blue-200">
                            You have submitted payment for some periods. Once staff verifies your payment, your schedule will be updated.
                        </flux:text>
                        <div class="mt-4 flex gap-3">
                            <flux:button :href="route('my.contributions')" wire:navigate variant="outline" size="sm">
                                View Recent Submissions
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Overdue Payments Alert -->
        @if($this->stats['overdue_count'] > 0 && $member->status === 'active')
            <div class="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-950">
                <div class="flex items-start gap-4">
                    <flux:icon name="exclamation-circle" class="size-8 text-red-600 dark:text-red-400" />
                    <div class="flex-1">
                        <flux:heading size="lg" class="text-red-900 dark:text-red-100">
                            You have {{ $this->stats['overdue_count'] }} overdue
                            {{ Str::plural('payment', $this->stats['overdue_count']) }}
                        </flux:heading>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div>
                                <flux:text class="text-xs text-red-700 dark:text-red-300">Total Overdue</flux:text>
                                <flux:text class="text-lg font-semibold text-red-900 dark:text-red-100">
                                    ₦{{ number_format($this->stats['overdue_amount'], 2) }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs text-red-700 dark:text-red-300">Late Fees</flux:text>
                                <flux:text class="text-lg font-semibold text-red-900 dark:text-red-100">
                                    ₦{{ number_format($this->stats['overdue_fines'], 2) }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs text-red-700 dark:text-red-300">Total Due</flux:text>
                                <flux:text class="text-lg font-semibold text-red-900 dark:text-red-100">
                                    ₦{{ number_format($this->stats['total_overdue'], 2) }}
                                </flux:text>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-3">
                            <flux:button :href="route('my.contributions')" wire:navigate variant="primary" size="sm">
                                View Payment Schedule
                            </flux:button>
                            <flux:button :href="route('my.contributions.submit')" wire:navigate variant="outline" size="sm">
                                Pay Now
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Next Payment Due Card -->
        @if($this->stats['next_payment'] && $member->status === 'active')
            @php
    $daysUntilDue = now()->diffInDays($this->stats['next_payment_due'], false);
    $isOverdue = $this->stats['next_payment_overdue'];
    $isDueSoon = $daysUntilDue >= 0 && $daysUntilDue <= 7;

    if ($isOverdue) {
        $bgColor = 'bg-red-50 dark:bg-red-950';
        $borderColor = 'border-red-200 dark:border-red-800';
        $textColor = 'text-red-900 dark:text-red-100';
        $iconColor = 'text-red-600 dark:text-red-400';
        $statusText = 'Overdue';
        $statusBg = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    } elseif ($isDueSoon) {
        $bgColor = 'bg-yellow-50 dark:bg-yellow-950';
        $borderColor = 'border-yellow-200 dark:border-yellow-800';
        $textColor = 'text-yellow-900 dark:text-yellow-100';
        $iconColor = 'text-yellow-600 dark:text-yellow-400';
        $statusText = 'Due Soon';
        $statusBg = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    } else {
        $bgColor = 'bg-green-50 dark:bg-green-950';
        $borderColor = 'border-green-200 dark:border-green-800';
        $textColor = 'text-green-900 dark:text-green-100';
        $iconColor = 'text-green-600 dark:text-green-400';
        $statusText = 'Upcoming';
        $statusBg = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    }
            @endphp
            <div class="rounded-xl border {{ $borderColor }} {{ $bgColor }} p-6">
                <div class="flex items-start gap-4">
                    <flux:icon name="calendar" class="size-8 {{ $iconColor }}" />
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg" class="{{ $textColor }}">
                                Next Payment Due
                            </flux:heading>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBg }}">
                                {{ $statusText }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <flux:text class="text-3xl font-bold {{ $textColor }}">
                                ₦{{ number_format($this->stats['next_payment_amount'], 2) }}
                            </flux:text>
                        </div>
                        <flux:text class="mt-2 {{ $textColor }}">
                            Due: {{ $this->stats['next_payment_due']->format('M d, Y') }}
                            @if($isOverdue)
                                ({{ abs($daysUntilDue) }} {{ Str::plural('day', abs($daysUntilDue)) }} overdue)
                            @elseif($isDueSoon)
                                (in {{ $daysUntilDue }} {{ Str::plural('day', $daysUntilDue) }})
                            @endif
                        </flux:text>
                        <div class="mt-4">
                            <flux:button :href="route('my.contributions.submit')" wire:navigate variant="primary" size="sm">
                                Pay Now
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

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
                <flux:button size="xs" href="{{ route('dependents.manage', $member) }}" wire:navigate class="mt-2">
                    {{ __('Manage') }}
                </flux:button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Quick Actions') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <flux:button icon="check-circle" :href="route('my.contributions.submit')" variant="outline"
                    wire:navigate>
                    Submit Payment
                </flux:button>

                <flux:button icon="currency-dollar" :href="route('my.loans.apply')" variant="outline" wire:navigate>
                    Apply for Loan
                </flux:button>

                <flux:button icon="heart" :href="route('my.claims.submit')" variant="outline" wire:navigate>
                    Submit Claim
                </flux:button>

                <flux:button icon="academic-cap" :href="route('my.programs.browse')" variant="outline" wire:navigate>
                    Browse Programs
                </flux:button>
            </div>
        </div>

        <!-- Upcoming Payments Schedule -->
        @if($this->upcomingPayments->count() > 0 && $member->status === 'active')
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:heading size="lg" class="text-gray-900 dark:text-white">
                            📅 Upcoming Payments
                        </flux:heading>
                        <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                            Your next {{ $this->upcomingPayments->count() }} scheduled payments
                        </flux:text>
                    </div>
                    <flux:button size="xs" :href="route('my.contributions')" wire:navigate>
                        View Full Schedule
                    </flux:button>
                </div>
                <div class="space-y-3">
                    @foreach($this->upcomingPayments as $payment)
                        @php
        $daysUntil = now()->diffInDays($payment->due_date, false);
        $isAwaiting = $payment->actualContribution && $payment->actualContribution->status === 'pending';
        $isOverdue = $payment->status === 'overdue';
        $isDueSoon = $daysUntil >= 0 && $daysUntil <= 7;

        if ($isAwaiting) {
            $statusBg = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            $statusText = 'Awaiting Verification';
        } elseif ($isOverdue) {
            $statusBg = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            $statusText = 'Overdue';
        } elseif ($isDueSoon) {
            $statusBg = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            $statusText = "Due in {$daysUntil} " . Str::plural('day', $daysUntil);
        } else {
            $statusBg = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            $statusText = "Due in {$daysUntil} " . Str::plural('day', $daysUntil);
        }
                        @endphp
                        <div
                            class="flex items-center justify-between py-3 border-b border-neutral-100 dark:border-neutral-700 last:border-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $payment->due_date->format('M d, Y') }}
                                        </p>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $payment->contributionPlan?->name ?? 'Regular Payment' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        ₦{{ number_format($payment->expected_amount, 2) }}
                                    </p>
                                    @if($payment->fine_amount > 0)
                                        <p class="text-xs text-red-600 dark:text-red-400">
                                            +₦{{ number_format($payment->fine_amount, 2) }} fine
                                        </p>
                                    @endif
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBg }}">
                                    {{ $statusText }}
                                </span>
                                @if(!$isAwaiting)
                                    <flux:button size="xs" :href="route('my.contributions.submit')" wire:navigate>
                                        Pay
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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