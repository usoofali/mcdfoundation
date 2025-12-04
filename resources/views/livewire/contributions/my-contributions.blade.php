<?php

use App\Models\Contribution;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'My Contributions'])] class extends Component {
    use WithPagination;

    public $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $member = auth()->user()->member;

        $query = $member->contributions()
            ->with(['contributionPlan'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy('payment_date', 'desc');

        return [
            'contributions' => $query->paginate(15),
            'stats' => [
                'total' => $member->contributions()->where('status', 'paid')->sum('amount'),
                'count' => $member->contributions()->where('status', 'paid')->count(),
                'pending' => $member->contributions()->where('status', 'pending')->count(),
                'fines' => $member->contributions()->where('status', 'paid')->sum('fine_amount'),
            ],
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('My Contributions') }}</h2>
        <flux:button :href="route('my.contributions.submit')" variant="primary" wire:navigate>
            {{ __('Submit Payment') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Paid') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    ₦{{ number_format($stats['total'], 2) }}
                </p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Payments') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['count'] }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Pending') }}</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['pending'] }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Fines') }}</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                    ₦{{ number_format($stats['fines'], 2) }}
                </p>
            </div>
        </div>

        <!-- Contributions List -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700">
            <!-- Filter -->
            <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center gap-4">
                    <flux:select wire:model.live="statusFilter" placeholder="All Status" class="w-48">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="paid">{{ __('Paid') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="overdue">{{ __('Overdue') }}</option>
                    </flux:select>
                </div>
            </div>

            <!-- Table -->
            @if($contributions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Receipt No') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Plan') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Amount') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Fine') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Payment Date') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            @foreach($contributions as $contribution)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $contribution->receipt_number }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {{ $contribution->contributionPlan?->label ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    ₦{{ number_format($contribution->amount, 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    @if($contribution->fine_amount > 0)
                                                        <span class="text-red-600 dark:text-red-400">
                                                            ₦{{ number_format($contribution->fine_amount, 2) }}
                                                        </span>
                                                    @else
                                                        <span class="text-neutral-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {{ $contribution->payment_date->format('M d, Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                                                    {{ $contribution->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                ($contribution->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                                        {{ ucfirst($contribution->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <flux:button :href="route('contributions.show', $contribution)" size="sm" wire:navigate>
                                                        {{ __('View') }}
                                                    </flux:button>
                                                </td>
                                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-neutral-200 dark:border-neutral-700">
                    {{ $contributions->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No contributions found') }}
                    </h3>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __('Get started by submitting your first payment.') }}
                    </p>
                    <div class="mt-6">
                        <flux:button :href="route('my.contributions.submit')" variant="primary" wire:navigate>
                            {{ __('Submit Payment') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>