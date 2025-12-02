<?php

use App\Models\HealthClaim;
use App\Services\HealthClaimService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Health Claim Details'])] class extends Component {
    use AuthorizesRequests;
    public HealthClaim $claim;
    public $remarks = '';
    public $showApproveModal = false;
    public $showRejectModal = false;
    public $showPayModal = false;

    public function mount(HealthClaim $claim): void
    {
        $this->authorize('view', $claim);
        $this->claim = $claim->load(['member', 'healthcareProvider', 'approver', 'rejecter', 'payer', 'documents', 'auditLogs']);
    }

    public function approveClaim(): void
    {
        $this->authorize('approve', $this->claim);

        try {
            $claimService = app(HealthClaimService::class);
            $claimService->approveClaim($this->claim, $this->remarks);

            session()->flash('success', 'Claim approved successfully.');
            $this->redirect(route('health-claims.show', $this->claim), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function rejectClaim(): void
    {
        $this->authorize('reject', $this->claim);

        $this->validate([
            'remarks' => 'required|string|min:10',
        ]);

        try {
            $claimService = app(HealthClaimService::class);
            $claimService->rejectClaim($this->claim, $this->remarks);

            session()->flash('success', 'Claim rejected.');
            $this->redirect(route('health-claims.show', $this->claim), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function payClaim(): void
    {
        $this->authorize('pay', $this->claim);

        try {
            $claimService = app(HealthClaimService::class);
            $claimService->payClaim($this->claim, $this->remarks);

            session()->flash('success', 'Claim payment processed successfully.');
            $this->redirect(route('health-claims.show', $this->claim), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deleteClaim(): void
    {
        $this->authorize('delete', $this->claim);

        try {
            $claimService = app(HealthClaimService::class);
            $claimService->deleteClaim($this->claim);

            session()->flash('success', 'Claim deleted successfully.');
            $this->redirect(route('health-claims.index'), navigate: true);
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
                <div class="flex items-center gap-3">
                    <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                        Claim #{{ $claim->claim_number }}
                    </flux:heading>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        @if($claim->status === 'submitted') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @elseif($claim->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @elseif($claim->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($claim->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @endif">
                        {{ $claim->status_label }}
                    </span>
                </div>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $claim->claim_type_label }} claim submitted on {{ $claim->created_at->format('M d, Y') }}
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" href="{{ route('health-claims.index') }}" wire:navigate>
                    Back to Claims
                </flux:button>
                @if($claim->status === 'submitted')
                    @can('update', $claim)
                        <flux:button variant="outline" href="{{ route('health-claims.edit', $claim) }}" wire:navigate>
                            Edit
                        </flux:button>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Member Information -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Member Information
                </flux:heading>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Full Name</flux:text>
                        <flux:text class="font-medium text-neutral-900 dark:text-white">{{ $claim->member->full_name }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Registration No</flux:text>
                        <flux:text class="font-medium text-neutral-900 dark:text-white">
                            {{ $claim->member->registration_no }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Phone</flux:text>
                        <flux:text class="font-medium text-neutral-900 dark:text-white">{{ $claim->member->phone }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Email</flux:text>
                        <flux:text class="font-medium text-neutral-900 dark:text-white">{{ $claim->member->email }}
                        </flux:text>
                    </div>
                </div>
            </div>

            <!-- Claim Details -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Claim Details
                </flux:heading>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Claim Type</flux:text>
                        <flux:text class="font-medium text-neutral-900 dark:text-white">{{ $claim->claim_type_label }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Healthcare Provider
                        </flux:text>
                        <flux:text class="font-medium text-neutral-900 dark:text-white">
                            {{ $claim->healthcareProvider->name }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Claim Date</flux:text>
                        <flux:text class="font-medium text-neutral-900 dark:text-white">
                            {{ $claim->claim_date->format('M d, Y') }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Submitted On</flux:text>
                        <flux:text class="font-medium text-neutral-900 dark:text-white">
                            {{ $claim->created_at->format('M d, Y h:i A') }}
                        </flux:text>
                    </div>
                </div>
            </div>

            <!-- Financial Breakdown -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Financial Breakdown
                </flux:heading>
                <div class="space-y-3">
                    <div
                        class="flex justify-between items-center pb-3 border-b border-neutral-200 dark:border-neutral-700">
                        <flux:text class="text-neutral-600 dark:text-neutral-400">Billed Amount</flux:text>
                        <flux:text class="text-lg font-semibold text-neutral-900 dark:text-white">
                            ₦{{ number_format($claim->billed_amount, 2) }}</flux:text>
                    </div>
                    <div class="flex justify-between items-center">
                        <flux:text class="text-neutral-600 dark:text-neutral-400">Coverage
                            ({{ $claim->coverage_percent }}%)</flux:text>
                        <flux:text class="text-lg font-semibold text-green-600 dark:text-green-400">
                            ₦{{ number_format($claim->covered_amount, 2) }}</flux:text>
                    </div>
                    <div
                        class="flex justify-between items-center pt-3 border-t border-neutral-200 dark:border-neutral-700">
                        <flux:text class="text-neutral-600 dark:text-neutral-400">Member Copay</flux:text>
                        <flux:text class="text-lg font-semibold text-neutral-900 dark:text-white">
                            ₦{{ number_format($claim->copay_amount, 2) }}</flux:text>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            @if($claim->remarks)
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="sm" class="mb-2 font-medium text-neutral-900 dark:text-white">
                        Remarks
                    </flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">{{ $claim->remarks }}</flux:text>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Actions -->
            @if($claim->status === 'submitted')
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                        Actions
                    </flux:heading>
                    <div class="space-y-2">
                        @can('approve', $claim)
                            <flux:button variant="primary" class="w-full" wire:click="$set('showApproveModal', true)">
                                Approve Claim
                            </flux:button>
                        @endcan
                        @can('reject', $claim)
                            <flux:button variant="danger" class="w-full" wire:click="$set('showRejectModal', true)">
                                Reject Claim
                            </flux:button>
                        @endcan
                        @can('delete', $claim)
                            <flux:button variant="ghost" class="w-full" wire:click="deleteClaim"
                                wire:confirm="Are you sure you want to delete this claim?">
                                Delete Claim
                            </flux:button>
                        @endcan
                    </div>
                </div>
            @elseif($claim->status === 'approved')
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                        Actions
                    </flux:heading>
                    @can('pay', $claim)
                        <flux:button variant="primary" class="w-full" wire:click="$set('showPayModal', true)">
                            Process Payment
                        </flux:button>
                    @endcan
                </div>
            @endif

            <!-- Status Timeline -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Status Timeline
                </flux:heading>
                <div class="space-y-4">
                    <!-- Submitted -->
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                                <flux:icon name="check" class="size-4 text-green-600 dark:text-green-400" />
                            </div>
                            @if($claim->status !== 'submitted')
                                <div class="h-full w-0.5 bg-neutral-200 dark:bg-neutral-700"></div>
                            @endif
                        </div>
                        <div class="flex-1 pb-4">
                            <flux:text class="font-medium text-neutral-900 dark:text-white">Submitted</flux:text>
                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $claim->created_at->format('M d, Y h:i A') }}
                            </flux:text>
                        </div>
                    </div>

                    <!-- Approved/Rejected -->
                    @if(in_array($claim->status, ['approved', 'paid', 'rejected']))
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full 
                                                        @if($claim->status === 'rejected') bg-red-100 dark:bg-red-900/20
                                                        @else bg-green-100 dark:bg-green-900/20
                                                        @endif">
                                    <flux:icon name="@if($claim->status === 'rejected') x-mark @else check @endif"
                                        class="size-4 @if($claim->status === 'rejected') text-red-600 dark:text-red-400 @else text-green-600 dark:text-green-400 @endif" />
                                </div>
                                @if($claim->status === 'paid')
                                    <div class="h-full w-0.5 bg-neutral-200 dark:bg-neutral-700"></div>
                                @endif
                            </div>
                            <div class="flex-1 pb-4">
                                <flux:text class="font-medium text-neutral-900 dark:text-white">
                                    {{ $claim->status === 'rejected' ? 'Rejected' : 'Approved' }}
                                </flux:text>
                                @if($claim->status === 'rejected' && $claim->rejecter)
                                    <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                        By {{ $claim->rejecter->name }}
                                    </flux:text>
                                    @if($claim->rejection_date)
                                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $claim->rejection_date->format('M d, Y') }}
                                        </flux:text>
                                    @endif
                                @elseif($claim->approver)
                                    <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                        By {{ $claim->approver->name }}
                                    </flux:text>
                                    @if($claim->approval_date)
                                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $claim->approval_date->format('M d, Y') }}
                                        </flux:text>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Paid -->
                    @if($claim->status === 'paid')
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                                    <flux:icon name="banknotes" class="size-4 text-green-600 dark:text-green-400" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <flux:text class="font-medium text-neutral-900 dark:text-white">Paid</flux:text>
                                <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ $claim->paid_date ? $claim->paid_date->format('M d, Y') : 'N/A' }}
                                </flux:text>
                                @if($claim->payer)
                                    <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">By
                                        {{ $claim->payer->name }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Documents Section -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Documents ({{ $claim->documents->count() }})
                </flux:heading>

                @if($claim->documents->count() > 0)
                    <div class="space-y-2">
                        @foreach($claim->documents as $document)
                            <div
                                class="flex items-center justify-between p-2 rounded border border-neutral-200 dark:border-neutral-700">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <flux:icon name="document" class="size-4 text-neutral-500 flex-shrink-0" />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-neutral-900 dark:text-white truncate">
                                            {{ $document->file_name }}</p>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $document->document_type_label }} • {{ $document->file_size_human }}
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($document->file_path) }}" download="{{ $document->file_name }}"
                                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400 flex-shrink-0 ml-2">
                                    <flux:icon name="arrow-down-tray" class="size-4" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">No documents uploaded</p>
                @endif
            </div>

            <!-- Audit Trail Section -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Audit Trail
                </flux:heading>

                @if($claim->auditLogs && $claim->auditLogs->count() > 0)
                    <div class="space-y-3">
                        @foreach($claim->auditLogs->take(5) as $log)
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <p class="text-sm font-medium text-neutral-900 dark:text-white">{{ $log->action }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                    by {{ $log->user->name ?? 'System' }} on {{ $log->created_at->format('M d, Y h:i A') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">No audit logs available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <flux:modal name="approve-claim" wire:model="showApproveModal">
        <form wire:submit="approveClaim">
            <div class="space-y-4">
                <flux:heading size="lg">Approve Claim</flux:heading>
                <flux:text>Are you sure you want to approve this claim for
                    ₦{{ number_format($claim->covered_amount, 2) }}?</flux:text>

                <div>
                    <flux:label>Remarks (Optional)</flux:label>
                    <flux:textarea wire:model="remarks" rows="3" placeholder="Add any remarks..."></flux:textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showApproveModal', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Approve</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Reject Modal -->
    <flux:modal name="reject-claim" wire:model="showRejectModal">
        <form wire:submit="rejectClaim">
            <div class="space-y-4">
                <flux:heading size="lg">Reject Claim</flux:heading>
                <flux:text>Please provide a reason for rejecting this claim.</flux:text>

                <div>
                    <flux:label>Reason for Rejection *</flux:label>
                    <flux:textarea wire:model="remarks" rows="3"
                        placeholder="Explain why this claim is being rejected..." required></flux:textarea>
                    @error('remarks') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showRejectModal', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="danger">Reject</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Pay Modal -->
    <flux:modal name="pay-claim" wire:model="showPayModal">
        <form wire:submit="payClaim">
            <div class="space-y-4">
                <flux:heading size="lg">Process Payment</flux:heading>
                <flux:text>Confirm payment of ₦{{ number_format($claim->covered_amount, 2) }} to
                    {{ $claim->healthcareProvider->name }}.
                </flux:text>

                <div>
                    <flux:label>Payment Notes (Optional)</flux:label>
                    <flux:textarea wire:model="remarks" rows="3" placeholder="Add any payment notes..."></flux:textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showPayModal', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Process Payment</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>