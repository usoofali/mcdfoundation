<?php

use App\Models\Contribution;
use App\Services\ContributionService;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Verify Contributions'])] class extends Component {
    public $filters = [
        'search' => '',
        'payment_method' => '',
        'date_from' => '',
        'date_to' => '',
    ];

    public $selectedContribution = null;
    public $showVerificationModal = false;
    public $verificationNotes = '';

    public function mount(): void
    {
        // Set default date range to last 30 days
        $this->filters['date_from'] = now()->subDays(30)->format('Y-m-d');
        $this->filters['date_to'] = now()->format('Y-m-d');
    }

    public function verifyContribution(Contribution $contribution, bool $approved): void
    {
        $this->selectedContribution = $contribution;
        $this->showVerificationModal = true;
        $this->verificationNotes = '';
    }

    public function confirmVerification(bool $approved): void
    {
        if (!$this->selectedContribution) {
            return;
        }

        $contributionService = app(ContributionService::class);

        try {
            $contributionService->verifyContribution(
                $this->selectedContribution, 
                $approved, 
                $this->verificationNotes
            );

            $status = $approved ? 'approved' : 'rejected';
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Contribution {$status} successfully!",
            ]);
            
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to verify contribution: ' . $e->getMessage(),
            ]);
        }
    }

    public function closeModal(): void
    {
        $this->showVerificationModal = false;
        $this->selectedContribution = null;
        $this->verificationNotes = '';
    }

    public function clearFilters(): void
    {
        $this->filters = [
            'search' => '',
            'payment_method' => '',
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];
    }

    public function getContributionsProperty()
    {
        $contributionService = app(ContributionService::class);
        return $contributionService->getPendingVerifications($this->filters);
    }

    public function getStatsProperty()
    {
        $contributionService = app(ContributionService::class);
        $pendingContributions = $contributionService->getPendingVerifications($this->filters, 1000);
        $pendingCollection = method_exists($pendingContributions, 'getCollection')
            ? $pendingContributions->getCollection()
            : collect($pendingContributions);
        
        return [
            'total_pending' => method_exists($pendingContributions, 'total') ? $pendingContributions->total() : $pendingCollection->count(),
            'by_method' => $pendingCollection->groupBy('payment_method')->map->count(),
            'by_date' => $pendingCollection->groupBy(function($item) {
                return $item->created_at->format('Y-m-d');
            })->map->count(),
        ];
    }

    public function getPaymentMethodOptionsProperty()
    {
        return [
            'transfer' => 'Bank Transfer',
            'bank_deposit' => 'Bank Deposit',
            'mobile_money' => 'Mobile Money',
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1.5">
                    <flux:heading size="xl" class="font-bold text-neutral-900 dark:text-white">
                        Verify Contributions
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        Review and verify member-submitted contributions
                    </flux:text>
                </div>
                <div class="grid max-w-xs grid-cols-1 gap-3 sm:w-auto">
                    <div class="rounded-xl border border-yellow-200 bg-white p-4 sm:p-5 dark:border-yellow-900/40 dark:bg-neutral-800">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-yellow-100 p-2 sm:p-3 dark:bg-yellow-900/20">
                                <flux:icon name="inbox" class="size-5 sm:size-6 text-yellow-600 dark:text-yellow-300" />
                            </div>
                            <div>
                                <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                    Pending
                                </flux:text>
                                <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                                    {{ $this->stats['total_pending'] }}
                                </flux:heading>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <flux:heading size="md" class="font-semibold text-neutral-900 dark:text-white">
                    Filters
                </flux:heading>
                <flux:button icon="arrow-path" variant="outline" wire:click="clearFilters" class="gap-2 sm:w-auto">
                    
                    Clear Filters
                </flux:button>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <flux:input 
                    wire:model.live.debounce.300ms="filters.search" 
                    label="Search"
                    placeholder="Member name, receipt number..."
                />

                <flux:select 
                    wire:model="filters.payment_method" 
                    label="Payment Method"
                    placeholder="All methods"
                >
                    <option value="">All Methods</option>
                    @foreach($this->paymentMethodOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:input 
                    wire:model="filters.date_from" 
                    label="From Date"
                    type="date"
                />

                <flux:input 
                    wire:model="filters.date_to" 
                    label="To Date"
                    type="date"
                />
            </div>
        </div>

        <!-- Contributions List -->
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="border-b border-neutral-200 px-4 py-4 sm:px-6 dark:border-neutral-700">
                <flux:heading size="md" class="font-semibold text-neutral-900 dark:text-white">
                    Pending Verifications
                </flux:heading>
            </div>

            @if($this->contributions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    Member
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    Plan
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    Method
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    Reference
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    Receipt
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    Submitted
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                            @foreach($this->contributions as $contribution)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700/60">
                                    <td class="px-6 py-4">
                                        <div class="space-y-1">
                                            <flux:text class="text-sm font-medium text-neutral-900 dark:text-white">
                                                {{ $contribution->member->full_name }}
                                            </flux:text>
                                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                                {{ $contribution->member->registration_no }}
                                            </flux:text>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <flux:text class="text-sm font-medium text-neutral-900 dark:text-white">
                                            {{ $contribution->contributionPlan?->label }}
                                        </flux:text>
                                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ ucfirst($contribution->contributionPlan->frequency) }}
                                        </flux:text>
                                    </td>
                                    <td class="px-6 py-4">
                                        <flux:text class="text-sm font-semibold text-neutral-900 dark:text-white">
                                            ₦{{ number_format($contribution->amount) }}
                                        </flux:text>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
                                            {{ $contribution->payment_method_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-sm text-neutral-900 dark:text-white">
                                            {{ $contribution->payment_reference }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($contribution->has_receipt)
                                            <flux:button 
                                                variant="outline" 
                                                size="sm"
                                                icon="document-text" 
                                                class="gap-2"
                                                wire:click="$dispatch('open-receipt-modal', { contributionId: {{ $contribution->id }} })"
                                            >
                                                
                                                View Receipt
                                            </flux:button>
                                        @else
                                            <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                                                No receipt
                                            </flux:text>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <flux:text class="text-sm text-neutral-900 dark:text-white">
                                            {{ $contribution->created_at->format('M d, Y') }}
                                        </flux:text>
                                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $contribution->created_at->format('h:i A') }}
                                        </flux:text>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:button 
                                                variant="primary" 
                                                size="sm"
                                                class="gap-2"
                                                wire:click="verifyContribution({{ $contribution->id }}, true)"
                                            >
                                                <flux:icon name="check" class="size-4" />
                                                Approve
                                            </flux:button>
                                            <flux:button 
                                                variant="danger" 
                                                size="sm"
                                                icon="x-mark" 
                                                class="gap-2"
                                                wire:click="verifyContribution({{ $contribution->id }}, false)"
                                            >
                                                
                                                Reject
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-neutral-200 px-4 py-4 sm:px-6 dark:border-neutral-700">
                    {{ $this->contributions->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <flux:icon name="document-text" class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" />
                    <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                        No pending verifications
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        All contributions have been verified.
                    </flux:text>
                </div>
            @endif
        </div>
    </div>

    <!-- Verification Modal -->
    @if($showVerificationModal && $selectedContribution)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-neutral-900/60 transition-opacity" wire:click="closeModal" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="inline-block w-full max-w-xl transform overflow-hidden rounded-2xl border border-neutral-200 bg-white text-left shadow-xl transition-all dark:border-neutral-700 dark:bg-neutral-800 sm:my-8 sm:align-middle">
                    <div class="px-4 pt-5 pb-4 sm:px-6 sm:pb-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30 sm:h-10 sm:w-10">
                                <flux:icon name="check-circle" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="mt-3 w-full text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <flux:heading size="md" class="font-semibold text-neutral-900 dark:text-white" id="modal-title">
                                    Verify Contribution
                                </flux:heading>
                                <div class="mt-3 space-y-4">
                                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                                        <div class="flex justify-between text-sm">
                                            <span class="font-medium text-neutral-500 dark:text-neutral-400">Member</span>
                                            <span class="text-neutral-900 dark:text-white">{{ $selectedContribution->member->full_name }}</span>
                                        </div>
                                        <div class="mt-2 flex justify-between text-sm">
                                            <span class="font-medium text-neutral-500 dark:text-neutral-400">Amount</span>
                                            <span class="text-neutral-900 dark:text-white">₦{{ number_format($selectedContribution->amount) }}</span>
                                        </div>
                                        <div class="mt-2 flex justify-between text-sm">
                                            <span class="font-medium text-neutral-500 dark:text-neutral-400">Method</span>
                                            <span class="text-neutral-900 dark:text-white">{{ $selectedContribution->payment_method_label }}</span>
                                        </div>
                                        <div class="mt-2 flex justify-between text-sm">
                                            <span class="font-medium text-neutral-500 dark:text-neutral-400">Reference</span>
                                            <span class="font-mono text-neutral-900 dark:text-white">{{ $selectedContribution->payment_reference }}</span>
                                        </div>
                                    </div>

                                    <flux:textarea 
                                        wire:model="verificationNotes" 
                                        label="Verification Notes"
                                        placeholder="Add any notes about this verification..."
                                        rows="3"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-neutral-200 bg-neutral-50 px-4 py-3 sm:flex-row sm:justify-end sm:px-6 dark:border-neutral-700 dark:bg-neutral-900">
                        <flux:button 
                            variant="primary" 
                            icon="check" 
                            wire:click="confirmVerification(true)"
                            class="w-full gap-2 sm:w-auto"
                        >
                            
                            Approve Contribution
                        </flux:button>
                        <flux:button 
                            variant="danger" 
                            icon="x-mark" 
                            wire:click="confirmVerification(false)"
                            class="w-full gap-2 sm:w-auto"
                        >
                            
                            Reject Contribution
                        </flux:button>
                        <flux:button 
                            variant="outline" 
                            icon="x-circle" 
                            wire:click="closeModal"
                            class="w-full gap-2 sm:w-auto"
                        >
                            
                            Cancel
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
