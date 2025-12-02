<?php

use App\Models\Loan;
use App\Services\LoanService;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Loan Details'])] class extends Component {
    use WithPagination;

    public Loan $loan;

    public $activeTab = 'details';

    public function mount(Loan $loan): void
    {
        $this->loan = $loan;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function approveLoan(LoanService $loanService): void
    {
        try {
            $loanService->approveLoan($this->loan, 1, 'Approved by LG Coordinator');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Loan approved successfully.',
            ]);
            $this->loan->refresh();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to approve loan: ' . $e->getMessage(),
            ]);
        }
    }

    public function disburseLoan(LoanService $loanService): void
    {
        try {
            $loanService->disburseLoan($this->loan, 'Loan disbursed');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Loan disbursed successfully.',
            ]);
            $this->loan->refresh();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to disburse loan: ' . $e->getMessage(),
            ]);
        }
    }

    public function getRepaymentsProperty()
    {
        return $this->loan->repayments()->orderBy('payment_date', 'desc')->paginate(15);
    }

    public function getApprovalsProperty()
    {
        return $this->loan->approvals()->orderBy('approval_level', 'asc')->get();
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1.5">
                    <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                        Loan Details
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        Loan ID: #{{ $loan->id }}
                    </flux:text>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button variant="primary" icon="arrow-left" variant="ghost" href="{{ route('loans.index') }}"
                        class="gap-2">

                        Back to Loans
                    </flux:button>
                    @if($loan->status === 'pending')
                        <flux:button variant="primary" icon="check-badge" variant="primary" wire:click="approveLoan"
                            class="gap-2">

                            Approve Loan
                        </flux:button>
                    @elseif($loan->status === 'approved')
                        <flux:button variant="primary" icon="banknotes" variant="primary" wire:click="disburseLoan"
                            class="gap-2">

                            Disburse Loan
                        </flux:button>
                    @endif
                </div>
            </div>

            <!-- Loan Status -->
            <div class="mb-6">
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full 
                    @if($loan->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @elseif($loan->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                    @elseif($loan->status === 'disbursed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($loan->status === 'repaid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($loan->status === 'defaulted') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                    @endif">
                    {{ $loan->status_label }}
                </span>
            </div>

            <!-- Loan Summary -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Loan Amount
                    </flux:text>
                    <flux:heading size="lg" class="mt-2 font-semibold text-blue-600 dark:text-blue-300">
                        ₦{{ number_format($loan->amount, 2) }}
                    </flux:heading>
                </div>
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Total Repaid
                    </flux:text>
                    <flux:heading size="lg" class="mt-2 font-semibold text-green-600 dark:text-green-300">
                        ₦{{ number_format($loan->total_repaid, 2) }}
                    </flux:heading>
                </div>
                <div
                    class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Outstanding Balance
                    </flux:text>
                    <flux:heading size="lg" class="mt-2 font-semibold text-red-600 dark:text-red-300">
                        ₦{{ number_format($loan->outstanding_balance, 2) }}
                    </flux:heading>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="border-b border-neutral-200 dark:border-neutral-700">
                <nav class="-mb-px flex gap-6 px-6">
                    <button wire:click="setActiveTab('details')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'details' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                        Loan Details
                    </button>
                    <button wire:click="setActiveTab('repayments')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'repayments' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                        Repayments
                    </button>
                    <button wire:click="setActiveTab('approvals')"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'approvals' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                        Approval History
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- Loan Details Tab -->
                @if($activeTab === 'details')
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Member Information</h4>
                                <dl class="space-y-2">
                                    <div>
                                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">Member Name</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $loan->member->full_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">Registration Number</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $loan->member->registration_no }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">Phone</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $loan->member->phone ?? 'N/A' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Loan Information</h4>
                                <dl class="space-y-2">
                                    <div>
                                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">Loan Type</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $loan->loan_type_label }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">Repayment Mode</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $loan->repayment_mode_label }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">Repayment Period</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $loan->repayment_period }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">Start Date</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $loan->start_date->format('M d, Y') }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        @if($loan->item_description)
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Item Description</h4>
                                <p class="text-sm text-gray-700">{{ $loan->item_description }}</p>
                            </div>
                        @endif

                        @if($loan->security_description)
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Security/Collateral</h4>
                                <p class="text-sm text-gray-700">{{ $loan->security_description }}</p>
                            </div>
                        @endif

                        @if($loan->guarantor_name)
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Guarantor Information</h4>
                                <dl class="space-y-1">
                                    <div>
                                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">Name</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $loan->guarantor_name }}</dd>
                                    </div>
                                    @if($loan->guarantor_contact)
                                        <div>
                                            <dt class="text-sm text-neutral-500 dark:text-neutral-400">Contact</dt>
                                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $loan->guarantor_contact }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif

                        @if($loan->remarks)
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Remarks</h4>
                                <p class="text-sm text-neutral-700 dark:text-neutral-300">{{ $loan->remarks }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Repayments Tab -->
                @if($activeTab === 'repayments')
                    <div class="space-y-4">
                        @if($this->repayments->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                Date</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                Amount</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                Method</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                Reference</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                                Received By</th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                                        @foreach($this->repayments as $repayment)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {{ $repayment->payment_date->format('M d, Y') }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                    ₦{{ number_format($repayment->amount, 2) }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $repayment->payment_method_label }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $repayment->reference ?? 'N/A' }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $repayment->receiver->name }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @if($this->repayments->hasPages())
                                <div class="border-t border-neutral-200 dark:border-neutral-700 px-6 py-4">
                                    {{ $this->repayments->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No repayments</h3>
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">No repayments have been recorded
                                    for this loan yet.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Approvals Tab -->
                @if($activeTab === 'approvals')
                    <div class="space-y-4">
                        @if($this->approvals->count() > 0)
                            <div class="space-y-4">
                                @foreach($this->approvals as $approval)
                                    <div
                                        class="border rounded-lg p-4 {{ $approval->status === 'approved' ? 'bg-green-50 border-green-200' : ($approval->status === 'rejected' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200') }}">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $approval->approval_level_label }}
                                                </h4>
                                                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $approval->role }} - {{ $approval->approver->name }}
                                                </p>
                                            </div>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                            @if($approval->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @elseif($approval->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @endif">
                                                {{ $approval->status_label }}
                                            </span>
                                        </div>
                                        @if($approval->remarks)
                                            <p class="mt-2 text-sm text-neutral-700 dark:text-neutral-300">{{ $approval->remarks }}</p>
                                        @endif
                                        @if($approval->approved_at)
                                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                {{ $approval->approved_at->format('M d, Y g:i A') }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No approvals</h3>
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">No approval records found for
                                    this loan.</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>