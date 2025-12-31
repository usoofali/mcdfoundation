<?php

use App\Models\Contribution;
use App\Services\ContributionService;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

new class extends Component {
    public ?Contribution $contribution = null;
    public bool $showModal = false;
    public string $verificationNotes = '';
    public bool $isApproving = false;

    #[On('open-receipt-modal')]
    #[On('open-verification-modal')]
    public function openModal($contributionId): void
    {
        // Handle both simple ID and object format { contributionId: id }
        $id = is_array($contributionId) ? ($contributionId['contributionId'] ?? null) : $contributionId;

        if (!$id)
            return;

        $this->contribution = Contribution::with(['member', 'contributionPlan', 'uploader'])->find($id);

        if ($this->contribution) {
            $this->verificationNotes = $this->contribution->verification_notes ?? '';
            $this->showModal = true;
            $this->dispatch('open-modal', name: 'contribution-verification');
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->contribution = null;
        $this->verificationNotes = '';
        $this->isApproving = false;
    }

    public function approveContribution(ContributionService $contributionService): void
    {
        $this->isApproving = true;

        try {
            $contributionService->verifyContribution($this->contribution, true, $this->verificationNotes);

            $this->dispatch('notify', type: 'success', message: 'Contribution approved successfully!');
            $this->dispatch('contributionVerified');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to approve contribution: ' . $e->getMessage());
        } finally {
            $this->isApproving = false;
        }
    }

    public function rejectContribution(ContributionService $contributionService): void
    {
        try {
            $contributionService->verifyContribution($this->contribution, false, $this->verificationNotes);

            $this->dispatch('notify', type: 'info', message: 'Contribution rejected.');
            $this->dispatch('contributionVerified');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to reject contribution: ' . $e->getMessage());
        }
    }

    #[Computed]
    public function receiptFileExtension(): string
    {
        if (!$this->contribution || !$this->contribution->receipt_path) {
            return '';
        }

        return strtolower(pathinfo($this->contribution->receipt_path, PATHINFO_EXTENSION));
    }

    #[Computed]
    public function isImage(): bool
    {
        return in_array($this->receiptFileExtension, ['jpg', 'jpeg', 'png', 'gif']);
    }

    #[Computed]
    public function isPdf(): bool
    {
        return $this->receiptFileExtension === 'pdf';
    }

    public function canVerify(): bool
    {
        return auth()->user()->hasPermission('confirm_contributions') &&
            $this->contribution &&
            $this->contribution->status === 'pending';
    }
}; ?>

<div wire:key="verification-modal-root">
    <flux:modal name="contribution-verification" wire:model="showModal" class="max-w-2xl">
        @if($contribution)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Verification: {{ $contribution->receipt_number }}</flux:heading>
                    <flux:subheading>Review payment details and attached receipt</flux:subheading>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Column 1: Info -->
                    <div class="space-y-4">
                        <section>
                            <flux:text size="sm" class="font-semibold uppercase tracking-wider text-neutral-500">Member
                            </flux:text>
                            <div class="mt-1">
                                <flux:text class="font-medium">{{ $contribution->member->full_name }}</flux:text>
                                <flux:text size="sm" class="text-neutral-500">{{ $contribution->member->registration_no }}
                                </flux:text>
                            </div>
                        </section>

                        <section>
                            <flux:text size="sm" class="font-semibold uppercase tracking-wider text-neutral-500">Payment
                            </flux:text>
                            <div class="mt-1 space-y-1">
                                <div class="flex justify-between">
                                    <flux:text size="sm">Amount:</flux:text>
                                    <flux:text size="sm" class="font-semibold">₦{{ number_format($contribution->amount) }}
                                    </flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text size="sm">Method:</flux:text>
                                    <flux:text size="sm">{{ $contribution->payment_method_label }}</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text size="sm">Date:</flux:text>
                                    <flux:text size="sm">{{ $contribution->payment_date->format('M d, Y') }}</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text size="sm">Reference:</flux:text>
                                    <flux:text size="sm" class="font-mono text-xs">{{ $contribution->payment_reference }}
                                    </flux:text>
                                </div>
                            </div>
                        </section>

                        @if($this->canVerify())
                            <section class="space-y-2">
                                <flux:textarea label="Verification Notes" wire:model="verificationNotes"
                                    placeholder="Optional notes..." rows="3" />
                            </section>
                        @endif
                    </div>

                    <!-- Column 2: Receipt -->
                    <div class="space-y-4">
                        <flux:text size="sm" class="font-semibold uppercase tracking-wider text-neutral-500">Receipt Preview
                        </flux:text>

                        <div
                            class="border border-neutral-200 dark:border-neutral-700 rounded-lg overflow-hidden bg-neutral-50 dark:bg-neutral-900 flex flex-col items-center justify-center p-2 min-h-[200px]">
                            @if($contribution->has_receipt)
                                @if($this->isImage)
                                    <img src="{{ $contribution->receipt_url }}" alt="Receipt"
                                        class="max-w-full h-auto rounded shadow-sm max-h-[300px] object-contain">
                                @elseif($this->isPdf)
                                    <div class="w-full h-[300px]">
                                        <iframe src="{{ $contribution->receipt_url }}" class="w-full h-full border-none"></iframe>
                                    </div>
                                @else
                                    <div class="text-center p-4">
                                        <flux:icon name="document-text" class="size-10 mx-auto text-neutral-400" />
                                        <flux:text size="xs" class="mt-2">Unsupported preview</flux:text>
                                    </div>
                                @endif

                                <flux:button variant="ghost" size="sm" class="mt-2 w-full"
                                    href="{{ $contribution->receipt_url }}" target="_blank">
                                    <flux:icon name="magnifying-glass-plus" class="size-4 mr-2" />
                                    Open Full Receipt
                                </flux:button>
                            @else
                                <div class="text-center p-4">
                                    <flux:icon name="exclamation-circle" class="size-10 mx-auto text-neutral-400" />
                                    <flux:text size="xs" class="mt-2">No receipt found</flux:text>
                                </div>
                            @endif
                        </div>

                        <div
                            class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-900/30">
                            <flux:text size="xs" class="text-blue-700 dark:text-blue-300">
                                <strong>Submitted by:</strong> {{ $contribution->uploader->name ?? 'Unknown' }}<br>
                                <strong>On:</strong> {{ $contribution->created_at->format('M d, Y h:i A') }}
                            </flux:text>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-6 pt-6 border-t border-neutral-100 dark:border-neutral-800">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    @if($this->canVerify())
                        <flux:button variant="danger" wire:click="rejectContribution" wire:loading.attr="disabled">
                            <flux:icon name="x-mark" class="size-4 mr-2" wire:loading.remove wire:target="rejectContribution" />
                            <span wire:loading.remove wire:target="rejectContribution">Reject</span>
                            <span wire:loading wire:target="rejectContribution">Rejecting...</span>
                        </flux:button>

                        <flux:button variant="primary" wire:click="approveContribution" wire:loading.attr="disabled">
                            <flux:icon name="check" class="size-4 mr-2" wire:loading.remove wire:target="approveContribution" />
                            <span wire:loading.remove wire:target="approveContribution">Approve</span>
                            <span wire:loading wire:target="approveContribution">Approving...</span>
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>