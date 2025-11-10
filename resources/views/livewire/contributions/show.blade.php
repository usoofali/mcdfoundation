<?php

use App\Models\Contribution;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Contribution Details'])] class extends Component {
    public Contribution $contribution;

    public function mount(Contribution $contribution): void
    {
        $this->contribution = $contribution->load(['member', 'contributionPlan', 'collector', 'uploader', 'verifier']);
    }
}; ?>

<div>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Contribution Details</h3>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Receipt: {{ $contribution->receipt_number }}</p>
                    </div>
                    <div class="flex space-x-3">
                        <flux:button variant="outline" href="{{ route('contributions.index') }}">
                            Back to List
                        </flux:button>
                        @if($contribution->status === 'pending')
                            <flux:button variant="primary" href="{{ route('contributions.edit', $contribution) }}">
                                Edit
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Member Information -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Member Information</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Full Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->member->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Registration Number</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->member->registration_no }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->member->phone ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Address</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->member->address ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Contribution Details -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contribution Details</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Receipt Number</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $contribution->receipt_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Contribution Plan</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($contribution->contributionPlan->name) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Amount</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">₦{{ number_format($contribution->amount, 2) }}</dd>
                            </div>
                            @if($contribution->fine_amount > 0)
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Late Fine</dt>
                                    <dd class="mt-1 text-sm text-red-600 dark:text-red-400">₦{{ number_format($contribution->fine_amount, 2) }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Amount</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">₦{{ number_format($contribution->total_amount, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="mt-8 pt-8 border-t border-neutral-200 dark:border-neutral-700">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Payment Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Payment Method</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->payment_method_label }}</dd>
                                </div>
                                @if($contribution->payment_reference)
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Payment Reference</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $contribution->payment_reference }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Payment Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->payment_date->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Collected By</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->collector->name }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Period Start</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->period_start->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Period End</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->period_end->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($contribution->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($contribution->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @elseif($contribution->status === 'overdue') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                            @endif">
                                            {{ $contribution->status_label }}
                                        </span>
                                    </dd>
                                </div>
                                @if($contribution->is_late)
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Late Payment</dt>
                                        <dd class="mt-1 text-sm text-red-600 dark:text-red-400">Yes ({{ $contribution->payment_date->diffInDays($contribution->period_end) }} days late)</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Receipt Information -->
                @if($contribution->has_receipt)
                    <div class="mt-8 pt-8 border-t border-neutral-200 dark:border-neutral-700">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Payment Receipt</h4>
                        <div class="bg-neutral-50 dark:bg-neutral-900 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <flux:icon name="document-text" class="w-8 h-8 text-green-600 dark:text-green-400" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Receipt uploaded</p>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Submitted by {{ $contribution->uploader->name ?? 'Member' }}</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <flux:button 
                                        variant="outline" 
                                        size="sm"
                                        wire:click="$dispatch('open-receipt-modal', { contributionId: {{ $contribution->id }} })"
                                    >
                                        View Receipt
                                    </flux:button>
                                    <flux:button 
                                        variant="outline" 
                                        size="sm"
                                        href="{{ $contribution->receipt_url }}" 
                                        target="_blank"
                                    >
                                        Download
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Verification Information -->
                @if($contribution->verified_at)
                    <div class="mt-8 pt-8 border-t border-neutral-200 dark:border-neutral-700">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Verification Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Verified At</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->verified_at->format('F d, Y \a\t g:i A') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Verified By</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->verifier->name ?? 'Unknown' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div>
                                @if($contribution->verification_notes)
                                    <dl class="space-y-3">
                                        <div>
                                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Verification Notes</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->verification_notes }}</dd>
                                        </div>
                                    </dl>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif($contribution->is_member_submitted)
                    <div class="mt-8 pt-8 border-t border-neutral-200 dark:border-neutral-700">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <div class="flex">
                                <flux:icon name="clock" class="w-5 h-5 text-yellow-400" />
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Awaiting Verification</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>This contribution was submitted by the member and is pending staff verification.</p>
                                        <p class="mt-1">Submitted on {{ $contribution->created_at->format('F d, Y \a\t g:i A') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Notes -->
                @if($contribution->notes)
                    <div class="mt-8 pt-8 border-t border-neutral-200 dark:border-neutral-700">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Notes</h4>
                        <div class="bg-neutral-50 dark:bg-neutral-900 rounded-lg p-4">
                            <p class="text-sm text-neutral-700 dark:text-neutral-300">{{ $contribution->notes }}</p>
                        </div>
                    </div>
                @endif

                <!-- Timestamps -->
                <div class="mt-8 pt-8 border-t border-neutral-200 dark:border-neutral-700">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Record Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Created At</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->created_at->format('F d, Y \a\t g:i A') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Updated At</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $contribution->updated_at->format('F d, Y \a\t g:i A') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    @livewire('contributions.verification-modal')
</div>
