<?php

use App\Models\CashoutRequest;
use App\Services\CashoutService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Process Cashout Request'])] class extends Component {
    use AuthorizesRequests;

    public CashoutRequest $request;
    public $notes = '';
    public $approvedAmount;
    public $disbursementReference = '';
    public $rejectionReason = '';

    public function mount(CashoutRequest $request): void
    {
        $this->authorize('view', $request);
        $this->request = $request;
        $this->approvedAmount = $request->requested_amount;
    }

    public function verify(): void
    {
        $this->authorize('verify', $this->request);

        try {
            $cashoutService = app(CashoutService::class);
            $cashoutService->verifyRequest($this->request, true, $this->notes);

            session()->flash('success', 'Cashout request verified successfully.');
            $this->redirect(route('admin.cashout.index'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->request);

        $this->validate([
            'approvedAmount' => 'required|numeric|min:0',
        ]);

        try {
            $cashoutService = app(CashoutService::class);
            $cashoutService->approveRequest($this->request, $this->approvedAmount, $this->notes);

            session()->flash('success', 'Cashout request approved successfully.');
            $this->redirect(route('admin.cashout.index'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function disburse(): void
    {
        $this->authorize('disburse', $this->request);

        $this->validate([
            'disbursementReference' => 'required|string|max:255',
        ]);

        try {
            $cashoutService = app(CashoutService::class);
            $cashoutService->disburseRequest($this->request, $this->disbursementReference);

            session()->flash('success', 'Cashout disbursed successfully.');
            $this->redirect(route('admin.cashout.index'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function reject(): void
    {
        $this->authorize('reject', $this->request);

        $this->validate([
            'rejectionReason' => 'required|string|max:500',
        ]);

        try {
            $cashoutService = app(CashoutService::class);
            $cashoutService->rejectRequest($this->request, $this->rejectionReason);

            session()->flash('success', 'Cashout request rejected.');
            $this->redirect(route('admin.cashout.index'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Process Cashout Request #{{ $request->id }}
                </flux:heading>
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($request->status === 'verified') bg-blue-100 text-blue-800
                        @elseif($request->status === 'approved') bg-green-100 text-green-800
                        @elseif($request->status === 'disbursed') bg-green-100 text-green-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ $request->status_label }}
                    </span>
                </div>
            </div>
            <div>
                <flux:button variant="ghost" href="{{ route('admin.cashout.index') }}" wire:navigate>
                    Back to List
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Request Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Member Information -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium">Member Information</flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-600 dark:text-neutral-400">Name:</span>
                        <span class="font-medium">{{ $request->member->full_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600 dark:text-neutral-400">Registration No:</span>
                        <span class="font-medium">{{ $request->member->registration_no }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600 dark:text-neutral-400">Membership Since:</span>
                        <span class="font-medium">{{ $request->member->registration_date->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600 dark:text-neutral-400">Total Contributions:</span>
                        <span
                            class="font-medium">{{ $request->member->contributions()->where('status', 'paid')->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Amount Details -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium">Amount Details</flux:heading>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Requested Amount:</span>
                        <span class="text-sm font-medium">₦{{ number_format($request->requested_amount, 2) }}</span>
                    </div>
                    @if($request->approved_amount)
                        <div class="flex justify-between border-t pt-3">
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">Approved Amount:</span>
                            <span
                                class="text-sm font-medium text-green-600">₦{{ number_format($request->approved_amount, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Bank Details -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium">Bank Account Details</flux:heading>
                <div class="space-y-2 text-sm">
                    <div><span class="font-medium">Bank:</span> {{ $request->bank_name }}</div>
                    <div><span class="font-medium">Account Number:</span> {{ $request->account_number }}</div>
                    <div><span class="font-medium">Account Name:</span> {{ $request->account_name }}</div>
                </div>
            </div>

            @if($request->reason)
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="sm" class="mb-3 font-medium">Member's Reason</flux:heading>
                    <flux:text>{{ $request->reason }}</flux:text>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="space-y-6">
            @can('verify', $request)
                @if($request->status === 'pending')
                    <div
                        class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                        <flux:heading size="sm" class="mb-4 font-medium">Verify Request</flux:heading>
                        <div class="space-y-4">
                            <flux:textarea wire:model="notes" label="Verification Notes (Optional)" rows="3"></flux:textarea>
                            <div class="flex gap-2">
                                <flux:button wire:click="verify" variant="primary" class="flex-1">
                                    Verify
                                </flux:button>
                                <flux:button wire:click="$set('showRejectModal', true)" variant="danger" class="flex-1">
                                    Reject
                                </flux:button>
                            </div>
                            need>
                        </div>
                @endif
            @endcan

                @can('approve', $request)
                    @if($request->status === 'verified')
                        <div
                            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:heading size="sm" class="mb-4 font-medium">Approve Request</flux:heading>
                            <div class="space-y-4">
                                <flux:input wire:model="approvedAmount" type="number" step="0.01" label="Approved Amount" />
                                <flux:textarea wire:model="notes" label="Approval Notes (Optional)" rows="3"></flux:textarea>
                                <div class="flex gap-2">
                                    <flux:button wire:click="approve" variant="primary" class="flex-1">
                                        Approve
                                    </flux:button>
                                    <flux:button wire:click="$set('showRejectModal', true)" variant="danger" class="flex-1">
                                        Reject
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcan

                @can('disburse', $request)
                    @if($request->status === 'approved')
                        <div
                            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:heading size="sm" class="mb-4 font-medium">Disburse Payment</flux:heading>
                            <div class="space-y-4">
                                <flux:input wire:model="disbursementReference" label="Transaction Reference"
                                    placeholder="Enter payment reference" />
                                <flux:button wire:click="disburse" variant="primary" class="w-full">
                                    Mark as Disbursed
                                </flux:button>
                            </div>
                        </div>
                    @endif
                @endcan

                <!-- Timeline -->
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="sm" class="mb-4 font-medium">Timeline</flux:heading>
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <div class="w-2 h-2 mt-1.5 rounded-full bg-blue-600"></div>
                            <div class="flex-1 text-sm">
                                <div class="font-medium">Request Submitted</div>
                                <div class="text-xs text-neutral-500">{{ $request->created_at->format('M d, Y H:i') }}
                                </div>
                            </div>
                        </div>

                        @if($request->verified_at)
                            <div class="flex gap-2">
                                <div class="w-2 h-2 mt-1.5 rounded-full bg-blue-600"></div>
                                <div class="flex-1 text-sm">
                                    <div class="font-medium">Verified</div>
                                    <div class="text-xs text-neutral-500">{{ $request->verified_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($request->approved_at)
                            <div class="flex gap-2">
                                <div class="w-2 h-2 mt-1.5 rounded-full bg-green-600"></div>
                                <div class="flex-1 text-sm">
                                    <div class="font-medium">Approved</div>
                                    <div class="text-xs text-neutral-500">{{ $request->approved_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($request->disbursed_at)
                            <div class="flex gap-2">
                                <div class="w-2 h-2 mt-1.5 rounded-full bg-green-600"></div>
                                <div class="flex-1 text-sm">
                                    <div class="font-medium">Disbursed</div>
                                    <div class="text-xs text-neutral-500">{{ $request->disbursed_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        @if(isset($showRejectModal) && $showRejectModal)
            <flux:modal wire:model="showRejectModal" header="Reject Cashout Request">
                <div class="space-y-4">
                    <flux:textarea wire:model="rejectionReason" label="Reason for Rejection" rows="4" required />
                    <div class="flex gap-2 justify-end">
                        <flux:button wire:click="$set('showRejectModal', false)" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button wire:click="reject" variant="danger">
                            Reject Request
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif
    </div>