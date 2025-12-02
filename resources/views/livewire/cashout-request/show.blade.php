<?php

use App\Models\CashoutRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Cashout Request Details'])] class extends Component {
    use AuthorizesRequests;

    public CashoutRequest $request;

    public function mount(CashoutRequest $request): void
    {
        $this->authorize('view', $request);
        $this->request = $request;
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5 flex-1">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Cashout Request #{{ $request->id }}
                </flux:heading>
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        @if($request->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @elseif($request->status === 'verified') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @elseif($request->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($request->status === 'disbursed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @endif">
                        {{ $request->status_label }}
                    </span>
                </div>
            </div>
            <div>
                <flux:button variant="ghost" href="{{ route('dashboard') }}" wire:navigate>
                    Back to Dashboard
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Request Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Amount Details -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Amount Details
                </flux:heading>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Requested Amount:</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">
                            ₦{{ number_format($request->requested_amount, 2) }}
                        </span>
                    </div>
                    @if($request->approved_amount)
                        <div class="flex justify-between border-t border-neutral-200 dark:border-neutral-700 pt-3">
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">Approved Amount:</span>
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">
                                ₦{{ number_format($request->approved_amount, 2) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Bank Details -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Bank Account Details
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div><span class="font-medium">Bank:</span> {{ $request->bank_name }}</div>
                    <div><span class="font-medium">Account Number:</span> {{ $request->account_number }}</div>
                    <div><span class="font-medium">Account Name:</span> {{ $request->account_name }}</div>
                </div>
            </div>

            @if($request->reason)
                <!-- Reason -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="sm" class="mb-3 font-medium text-neutral-900 dark:text-white">
                        Reason for Cashout
                    </flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        {{ $request->reason }}
                    </flux:text>
                </div>
            @endif

            <!-- Timeline -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Request Timeline
                </flux:heading>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-2 h-2 mt-1.5 rounded-full bg-blue-600"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium">Request Submitted</div>
                            <div class="text-xs text-neutral-500">{{ $request->created_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>

                    @if($request->verified_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-2 h-2 mt-1.5 rounded-full bg-blue-600"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium">Verified by {{ $request->verifier->name }}</div>
                                <div class="text-xs text-neutral-500">{{ $request->verified_at->format('M d, Y H:i') }}
                                </div>
                                @if($request->verification_notes)
                                    <div class="text-xs text-neutral-600 mt-1">{{ $request->verification_notes }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($request->approved_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-2 h-2 mt-1.5 rounded-full bg-green-600"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium">Approved by {{ $request->approver->name }}</div>
                                <div class="text-xs text-neutral-500">{{ $request->approved_at->format('M d, Y H:i') }}
                                </div>
                                @if($request->approval_notes)
                                    <div class="text-xs text-neutral-600 mt-1">{{ $request->approval_notes }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($request->disbursed_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-2 h-2 mt-1.5 rounded-full bg-green-600"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium">Disbursed by {{ $request->disburser->name }}</div>
                                <div class="text-xs text-neutral-500">{{ $request->disbursed_at->format('M d, Y H:i') }}
                                </div>
                                @if($request->disbursement_reference)
                                    <div class="text-xs font-mono text-neutral-600 mt-1">Ref:
                                        {{ $request->disbursement_reference }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($request->rejected_at)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-2 h-2 mt-1.5 rounded-full bg-red-600"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-red-600">Rejected</div>
                                <div class="text-xs text-neutral-500">{{ $request->rejected_at->format('M d, Y H:i') }}
                                </div>
                                @if($request->rejection_reason)
                                    <div class="text-xs text-red-600 mt-1">{{ $request->rejection_reason }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="space-y-6">
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Current Status
                </flux:heading>
                <div class="space-y-3">
                    @if($request->status === 'pending')
                        <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-3">
                            <flux:text class="text-sm text-yellow-800 dark:text-yellow-200">
                                Your request is awaiting verification by our finance team.
                            </flux:text>
                        </div>
                    @elseif($request->status === 'verified')
                        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3">
                            <flux:text class="text-sm text-blue-800 dark:text-blue-200">
                                Your request has been verified and is awaiting final approval.
                            </flux:text>
                        </div>
                    @elseif($request->status === 'approved')
                        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3">
                            <flux:text class="text-sm text-green-800 dark:text-green-200">
                                Your request has been approved and is being processed for disbursement.
                            </flux:text>
                        </div>
                    @elseif($request->status === 'disbursed')
                        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3">
                            <flux:text class="text-sm text-green-800 dark:text-green-200">
                                Cashout completed! Funds have been sent to your account.
                            </flux:text>
                        </div>
                    @elseif($request->status === 'rejected')
                        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3">
                            <flux:text class="text-sm text-red-800 dark:text-red-200">
                                Your request was rejected. See timeline for details.
                            </flux:text>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>