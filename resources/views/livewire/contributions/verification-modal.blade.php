<?php

use App\Models\Contribution;
use App\Services\ContributionService;
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public Contribution $contribution;
    public bool $showModal = false;
    public string $verificationNotes = '';
    public bool $isApproving = false;

    protected $listeners = ['open-receipt-modal' => 'openModal', 'open-verification-modal' => 'openModal'];

    public function openModal($contributionId): void
    {
        $this->contribution = Contribution::with(['member', 'contributionPlan', 'uploader'])->find($contributionId);
        $this->verificationNotes = $this->contribution->verification_notes ?? '';
        $this->showModal = true;
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
            
            $this->dispatch('alert', type: 'success', message: 'Contribution approved successfully!');
            $this->dispatch('contributionVerified');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: 'Failed to approve contribution: ' . $e->getMessage());
        } finally {
            $this->isApproving = false;
        }
    }

    public function rejectContribution(ContributionService $contributionService): void
    {
        try {
            $contributionService->verifyContribution($this->contribution, false, $this->verificationNotes);
            
            $this->dispatch('alert', type: 'info', message: 'Contribution rejected.');
            $this->dispatch('contributionVerified');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: 'Failed to reject contribution: ' . $e->getMessage());
        }
    }

    public function getReceiptFileExtensionProperty(): string
    {
        if (!$this->contribution || !$this->contribution->receipt_path) {
            return '';
        }
        
        return strtolower(pathinfo($this->contribution->receipt_path, PATHINFO_EXTENSION));
    }

    public function getIsImageProperty(): bool
    {
        return in_array($this->receiptFileExtension, ['jpg', 'jpeg', 'png', 'gif']);
    }

    public function getIsPdfProperty(): bool
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

<div>
    @if($showModal && $contribution)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal" aria-hidden="true"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="w-full">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Payment Receipt - {{ $contribution->receipt_number }}
                                    </h3>
                                    <flux:button variant="outline" size="sm" wire:click="closeModal">
                                        <flux:icon name="x-mark" class="w-4 h-4" />
                                    </flux:button>
                                </div>
                                
                                <!-- Contribution Details -->
                                <div class="bg-gray-50 rounded-md p-4 mb-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Member Information</h4>
                                            <div class="space-y-1 text-sm">
                                                <div><span class="font-medium">Name:</span> {{ $contribution->member->full_name }}</div>
                                                <div><span class="font-medium">Registration:</span> {{ $contribution->member->registration_no }}</div>
                                                <div><span class="font-medium">Phone:</span> {{ $contribution->member->phone ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Payment Information</h4>
                                            <div class="space-y-1 text-sm">
                                                <div><span class="font-medium">Amount:</span> ₦{{ number_format($contribution->amount) }}</div>
                                                <div><span class="font-medium">Method:</span> {{ $contribution->payment_method_label }}</div>
                                                <div><span class="font-medium">Reference:</span> <span class="font-mono">{{ $contribution->payment_reference }}</span></div>
                                                <div><span class="font-medium">Date:</span> {{ $contribution->payment_date->format('M d, Y') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Receipt Display -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Uploaded Receipt</h4>
                                    
                                    @if($contribution->has_receipt)
                                        <div class="flex justify-center">
                                            @if($isImage)
                                                <img 
                                                    src="{{ $contribution->receipt_url }}" 
                                                    alt="Payment Receipt" 
                                                    class="max-w-full max-h-96 rounded-lg shadow-sm"
                                                    style="max-height: 500px;"
                                                />
                                            @elseif($isPdf)
                                                <div class="w-full">
                                                    <iframe 
                                                        src="{{ $contribution->receipt_url }}" 
                                                        class="w-full h-96 rounded-lg border"
                                                        style="min-height: 500px;"
                                                    ></iframe>
                                                </div>
                                            @else
                                                <div class="text-center py-8">
                                                    <flux:icon name="document-text" class="mx-auto h-12 w-12 text-gray-400" />
                                                    <p class="mt-2 text-sm text-gray-500">Unsupported file format</p>
                                                    <flux:button 
                                                        variant="outline" 
                                                        size="sm" 
                                                        href="{{ $contribution->receipt_url }}" 
                                                        target="_blank"
                                                        class="mt-2"
                                                    >
                                                        Download File
                                                    </flux:button>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="mt-4 text-center">
                                            <flux:button 
                                                variant="outline" 
                                                href="{{ $contribution->receipt_url }}" 
                                                target="_blank"
                                            >
                                                <flux:icon name="arrow-down-tray" class="w-4 h-4 mr-2" />
                                                Download Receipt
                                            </flux:button>
                                        </div>
                                    @else
                                        <div class="text-center py-8">
                                            <flux:icon name="exclamation-triangle" class="mx-auto h-12 w-12 text-yellow-400" />
                                            <p class="mt-2 text-sm text-gray-500">No receipt uploaded</p>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Submission Details -->
                                <div class="mt-4 bg-blue-50 rounded-md p-4">
                                    <h4 class="text-sm font-medium text-blue-800 mb-2">Submission Details</h4>
                                    <div class="text-sm text-blue-700 space-y-1">
                                        <div><span class="font-medium">Submitted by:</span> {{ $contribution->uploader->name ?? 'Unknown' }}</div>
                                        <div><span class="font-medium">Submitted on:</span> {{ $contribution->created_at->format('M d, Y h:i A') }}</div>
                                        <div><span class="font-medium">Status:</span> 
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Pending Verification
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Verification Notes -->
                                @if($this->canVerify())
                                    <div class="mt-6">
                                        <label for="verification_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                            Verification Notes (Optional)
                                        </label>
                                        <textarea 
                                            id="verification_notes" 
                                            wire:model="verificationNotes" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" 
                                            rows="3" 
                                            placeholder="Add any notes about this verification..."
                                        ></textarea>
                                        @error('verification_notes')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <div class="flex space-x-3">
                            <flux:button variant="outline" wire:click="closeModal">
                                Close
                            </flux:button>
                            
                            @if($this->canVerify())
                                <flux:button 
                                    variant="danger" 
                                    wire:click="rejectContribution"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="rejectContribution">Reject</span>
                                    <span wire:loading wire:target="rejectContribution">Rejecting...</span>
                                </flux:button>
                                
                                <flux:button 
                                    variant="primary" 
                                    wire:click="approveContribution"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="approveContribution">Approve</span>
                                    <span wire:loading wire:target="approveContribution">Approving...</span>
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
