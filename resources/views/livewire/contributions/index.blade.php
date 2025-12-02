<?php

use App\Models\Contribution;
use App\Models\Member;
use App\Models\ContributionPlan;
use App\Services\ContributionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Contributions'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $status = '';
    public $payment_method = '';
    public $member_id = '';
    public $date_from = '';
    public $date_to = '';
    public $perPage = 15;
    public $showFilters = false;

    public function mount(): void
    {
        // Initialize any default values
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function updatedMemberId(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->payment_method = '';
        $this->member_id = '';
        $this->date_from = '';
        $this->date_to = '';
        $this->resetPage();
    }

    public function getContributionsProperty()
    {
        $contributionService = app(ContributionService::class);

        $filters = [
            'search' => $this->search,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'member_id' => $this->member_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        return $contributionService->getContributions($filters, $this->perPage);
    }

    public function getStatsProperty()
    {
        $contributionService = app(ContributionService::class);

        $filters = [
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        return $contributionService->getContributionStats($filters);
    }

    public function getMembersProperty()
    {
        return Member::orderBy('full_name')->get();
    }

    public function getStatusOptionsProperty()
    {
        return [
            'paid' => 'Paid',
            'pending' => 'Pending',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ];
    }

    public function getPaymentMethodOptionsProperty()
    {
        return [
            'cash' => 'Cash',
            'transfer' => 'Bank Transfer',
            'bank_deposit' => 'Bank Deposit',
            'mobile_money' => 'Mobile Money',
        ];
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Contributions
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Manage member contributions and track payments
                </flux:text>
            </div>
            <div>
                <flux:button variant="primary" icon="plus" variant="primary" href="{{ route('contributions.create') }}" class="gap-2">

                    Record Contribution
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 p-2 sm:p-3 dark:bg-blue-900/20">
                    <flux:icon name="banknotes" class="size-5 sm:size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Total Amount
                    </flux:text>
                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                        ₦{{ number_format($this->stats['total_amount'], 2) }}
                    </flux:heading>
                </div>
            </div>
        </div>
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-100 p-2 sm:p-3 dark:bg-green-900/20">
                    <flux:icon name="check-badge" class="size-5 sm:size-6 text-green-600 dark:text-green-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Paid
                    </flux:text>
                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                        {{ $this->stats['paid_contributions'] }}
                    </flux:heading>
                </div>
            </div>
        </div>
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-amber-100 p-2 sm:p-3 dark:bg-amber-900/20">
                    <flux:icon name="clock" class="size-5 sm:size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Pending
                    </flux:text>
                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                        {{ $this->stats['pending_contributions'] }}
                    </flux:heading>
                </div>
            </div>
        </div>
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-red-100 p-2 sm:p-3 dark:bg-red-900/20">
                    <flux:icon name="exclamation-circle" class="size-5 sm:size-6 text-red-600 dark:text-red-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Overdue
                    </flux:text>
                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                        {{ $this->stats['overdue_contributions'] }}
                    </flux:heading>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="sm" class="font-medium text-neutral-900 dark:text-white">
                Filters
            </flux:heading>
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                Clear Filters
            </flux:button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <flux:input wire:model.live="search" placeholder="Search by receipt, member name, or reference"
                    icon="magnifying-glass" />
            </div>

            <div>
                <flux:input wire:model.live="status" placeholder="Filter by status" />
            </div>

            <div>
                <flux:input wire:model.live="payment_method" placeholder="Filter by payment method" />
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <flux:input wire:model.live="member_id" placeholder="Filter by member" />
            </div>

            <div>
                <flux:input wire:model.live="date_from" type="date" placeholder="From date" />
            </div>

            <div>
                <flux:input wire:model.live="date_to" type="date" placeholder="To date" />
            </div>
        </div>
    </div>

    <!-- Contributions Table -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        @if($this->contributions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Receipt</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Member</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Plan</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Amount</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Payment</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Receipt File</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                        @foreach($this->contributions as $contribution)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $contribution->receipt_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div>{{ $contribution->member->full_name }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $contribution->member->registration_no }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $contribution->contributionPlan?->label }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div>₦{{ number_format($contribution->amount, 2) }}</div>
                                    @if($contribution->fine_amount > 0)
                                        <div class="text-xs text-red-600 dark:text-red-400">
                                            +₦{{ number_format($contribution->fine_amount, 2) }} fine</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    <div>{{ $contribution->payment_method_label }}</div>
                                    @if($contribution->payment_reference)
                                        <div class="text-xs text-neutral-400 dark:text-neutral-500">
                                            {{ $contribution->payment_reference }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    @if($contribution->has_receipt)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="document-text" class="size-4 text-green-600 dark:text-green-400" />
                                            <span class="text-xs text-green-600 dark:text-green-400">Uploaded</span>
                                        </div>
                                        @if($contribution->is_member_submitted)
                                            <div class="text-xs text-neutral-400 dark:text-neutral-500">by
                                                {{ $contribution->uploader->name ?? 'Member' }}</div>
                                        @endif
                                    @else
                                        <span class="text-neutral-400 dark:text-neutral-500 text-xs">No receipt</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                     @if($contribution->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                     @elseif($contribution->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                     @elseif($contribution->status === 'overdue') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                     @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                    @endif">
                                        {{ $contribution->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $contribution->payment_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:button variant="ghost" size="sm"
                                            href="{{ route('contributions.show', $contribution) }}">
                                            View
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" icon="document-arrow-down"
                                            href="{{ route('contributions.receipt.download', $contribution) }}"
                                            title="Download Receipt">
                                        </flux:button>
                                        @if($contribution->has_receipt)
                                            <flux:button variant="outline" size="sm"
                                                wire:click="$dispatch('open-receipt-modal', { contributionId: {{ $contribution->id }} })">
                                                Receipt
                                            </flux:button>
                                        @endif
                                        @if($contribution->status === 'pending' && $contribution->is_member_submitted)
                                            <flux:button variant="primary" size="sm" href="{{ route('contributions.verify') }}">
                                                Verify
                                            </flux:button>
                                        @elseif($contribution->status === 'pending')
                                            <flux:button variant="ghost" size="sm"
                                                href="{{ route('contributions.edit', $contribution) }}">
                                                Edit
                                            </flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                {{ $this->contributions->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                    No contributions
                </flux:heading>
                <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Get started by recording a contribution.
                </flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" icon="plus" variant="primary" href="{{ route('contributions.create') }}" class="gap-2">

                        Record Contribution
                    </flux:button>
                </div>
            </div>
        @endif
    </div>

    <!-- Receipt Modal -->
    @livewire('contributions.verification-modal')
</div>