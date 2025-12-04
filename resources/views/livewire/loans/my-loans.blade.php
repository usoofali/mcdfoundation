<?php

use App\Models\Loan;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'My Loans'])] class extends Component {
    use WithPagination;

    public $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $member = auth()->user()->member;

        $query = $member->loans()
            ->with(['repayments'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy('created_at', 'desc');

        // Calculate outstanding balance from loans
        $outstandingBalance = $member->loans()
            ->whereIn('status', ['disbursed', 'defaulted'])
            ->get()
            ->sum(function ($loan) {
                return $loan->outstanding_balance;
            });

        return [
            'loans' => $query->paginate(10),
            'stats' => [
                'total' => $member->loans()->count(),
                'active' => $member->loans()->whereIn('status', ['approved', 'disbursed'])->count(),
                'total_amount' => $member->loans()->whereIn('status', ['disbursed', 'defaulted'])->sum('amount'),
                'outstanding' => $outstandingBalance,
            ],
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('My Loans') }}</h2>
        <flux:button :href="route('my.loans.apply')" variant="primary" wire:navigate>
            {{ __('Apply for Loan') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Loans') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Active Loans') }}</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Borrowed') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    ₦{{ number_format($stats['total_amount'], 2) }}
                </p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Outstanding') }}</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                    ₦{{ number_format($stats['outstanding'], 2) }}
                </p>
            </div>
        </div>

        <!-- Loans List -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700">
            <!-- Filter -->
            <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
                <flux:select wire:model.live="statusFilter" placeholder="All Status" class="w-48">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="disbursed">{{ __('Disbursed') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                </flux:select>
            </div>

            <!-- Loans -->
            @if($loans->count() > 0)
                <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @foreach($loans as $loan)
                            <div class="p-6 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $loan->loan_type_label }}
                                            </h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                                    {{ $loan->status === 'disbursed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                        ($loan->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                            ($loan->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                                ($loan->status === 'completed' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' :
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'))) }}">
                                                {{ $loan->status_label }}
                                            </span>
                                        </div>

                                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Amount') }}</p>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    ₦{{ number_format($loan->amount, 2) }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Outstanding') }}
                                                </p>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    ₦{{ number_format($loan->outstanding_balance, 2) }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Interest Rate') }}
                                                </p>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $loan->interest_rate }}%
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Start Date') }}</p>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $loan->start_date ? $loan->start_date->format('M d, Y') : 'N/A' }}
                                                </p>
                                            </div>
                                        </div>

                                        @if($loan->purpose)
                                            <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                                <span class="font-medium">{{ __('Purpose:') }}</span> {{ $loan->purpose }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="ml-4">
                                        <flux:button :href="route('my.loans.show', $loan)" size="sm" variant="outline"
                                            wire:navigate>
                                            {{ __('View Details') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-neutral-200 dark:border-neutral-700">
                    {{ $loans->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No loans found') }}</h3>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __('Get started by applying for your first loan.') }}
                    </p>
                    <div class="mt-6">
                        <flux:button :href="route('my.loans.apply')" variant="primary" wire:navigate>
                            {{ __('Apply for Loan') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>