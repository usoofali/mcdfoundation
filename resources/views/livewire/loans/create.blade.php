<?php

use App\Models\Member;
use App\Services\LoanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Create Loan'])] class extends Component {
    public $member_id = '';
    public $loan_type = 'cash';
    public $item_description = '';
    public $amount = 0;
    public $repayment_mode = 'installments';
    public $repayment_period = '6 months';
    public $start_date = '';
    public $security_description = '';
    public $guarantor_name = '';
    public $guarantor_contact = '';
    public $remarks = '';

    public $memberSearch = '';
    public $searchResults = [];

    public function mount(): void
    {
        $this->start_date = now()->addDays(7)->format('Y-m-d');
    }

    public function updatedMemberSearch(): void
    {
        if (strlen($this->memberSearch) > 2) {
            $this->searchResults = Member::where('full_name', 'like', '%' . $this->memberSearch . '%')
                ->orWhere('registration_no', 'like', '%' . $this->memberSearch . '%')
                ->limit(5)
                ->get()
                ->toArray();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectMember(int $memberId): void
    {
        $this->member_id = $memberId;
        $member = Member::find($memberId);
        $this->memberSearch = $member->full_name . ' (' . $member->registration_no . ')';
        $this->searchResults = [];
    }

    public function updatedLoanType(): void
    {
        if ($this->loan_type === 'cash') {
            $this->item_description = '';
        }
    }

    public function updatedRepaymentMode(): void
    {
        if ($this->repayment_mode === 'full') {
            $this->repayment_period = '1 month';
        }
    }

    public function updatedRepaymentPeriod(): void
    {
        // Auto-calculate installment amount if in installments mode
        if ($this->repayment_mode === 'installments' && $this->amount > 0) {
            $months = $this->getRepaymentPeriodInMonths();
            if ($months > 0) {
                // This will be handled by the model's boot method
            }
        }
    }

    public function updatedAmount(): void
    {
        // Auto-calculate installment amount if in installments mode
        if ($this->repayment_mode === 'installments' && $this->amount > 0) {
            $months = $this->getRepaymentPeriodInMonths();
            if ($months > 0) {
                // This will be handled by the model's boot method
            }
        }
    }

    public function submitLoanApplication(LoanService $loanService): void
    {
        $this->validate([
            'member_id' => 'required|exists:members,id',
            'loan_type' => 'required|in:cash,item',
            'item_description' => 'required_if:loan_type,item|nullable|string|max:255',
            'amount' => 'required|numeric|min:1000|max:1000000',
            'repayment_mode' => 'required|in:installments,full',
            'repayment_period' => 'required|string|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'security_description' => 'nullable|string|max:1000',
            'guarantor_name' => 'nullable|string|max:150',
            'guarantor_contact' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $data = [
                'member_id' => $this->member_id,
                'loan_type' => $this->loan_type,
                'item_description' => $this->item_description,
                'amount' => $this->amount,
                'repayment_mode' => $this->repayment_mode,
                'repayment_period' => $this->repayment_period,
                'start_date' => $this->start_date,
                'security_description' => $this->security_description,
                'guarantor_name' => $this->guarantor_name,
                'guarantor_contact' => $this->guarantor_contact,
                'remarks' => $this->remarks,
            ];

            $loan = $loanService->createLoanApplication($data);

            session()->flash('success', 'Loan application submitted successfully. Application ID: #' . $loan->id);

            $this->redirect(route('loans.index'));
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to submit loan application: ' . $e->getMessage(),
            ]);
        }
    }

    public function getRepaymentPeriodInMonths(): int
    {
        // Extract number from period string like "6 months", "12 months"
        preg_match('/(\d+)/', $this->repayment_period, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 6;
    }

    public function getRepaymentPeriodOptionsProperty(): array
    {
        return [
            '3 months' => '3 months',
            '6 months' => '6 months',
            '12 months' => '12 months',
            '18 months' => '18 months',
            '24 months' => '24 months',
        ];
    }

    public function getLoanTypeOptionsProperty(): array
    {
        return [
            'cash' => 'Cash Loan',
            'item' => 'Item Loan',
        ];
    }

    public function getRepaymentModeOptionsProperty(): array
    {
        return [
            'installments' => 'Installments',
            'full' => 'Full Payment',
        ];
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
                        Apply for Loan
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        Submit a new loan application
                    </flux:text>
                </div>
                <div>
                    <flux:button variant="primary" icon="arrow-left" variant="ghost" href="{{ route('loans.index') }}"
                        class="gap-2">

                        Back to Loans
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Loan Application Form -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="submitLoanApplication" class="space-y-6">
                <!-- Member Selection -->
                <div>
                    <flux:label for="member_search" value="Search Member" />
                    <flux:input id="member_search" wire:model.live="memberSearch"
                        placeholder="Search by name or registration number" required />
                    @error('member_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                    @if(!empty($searchResults))
                        <ul
                            class="mt-2 max-h-60 overflow-y-auto rounded-md border border-neutral-300 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                            @foreach($searchResults as $member)
                                <li wire:key="member-{{ $member['id'] }}" wire:click="selectMember({{ $member['id'] }})"
                                    class="p-3 hover:bg-neutral-100 dark:hover:bg-neutral-700 cursor-pointer flex justify-between items-center">
                                    <span class="text-gray-900 dark:text-white">{{ $member['full_name'] }}
                                        ({{ $member['registration_no'] }})</span>
                                    @if($member_id === $member['id'])
                                        <flux:icon name="check-circle" class="text-green-500 dark:text-green-400" />
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @elseif($memberSearch && empty($searchResults) && !$member_id)
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-2">No members found matching
                            "{{ $memberSearch }}"</p>
                    @endif
                </div>

                <!-- Loan Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:label for="loan_type" value="Loan Type" />
                        <flux:input id="loan_type" wire:model.live="loan_type" placeholder="Select loan type"
                            required />
                        @error('loan_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    @if($loan_type === 'item')
                        <div>
                            <flux:label for="item_description" value="Item Description" />
                            <flux:textarea id="item_description" wire:model="item_description"
                                placeholder="Describe the item you want to purchase" required />
                            @error('item_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>

                <!-- Amount -->
                <div>
                    <flux:label for="amount" value="Loan Amount" />
                    <flux:input id="amount" wire:model.live="amount" type="number" step="0.01"
                        placeholder="e.g., 50000.00" required />
                    @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Repayment Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:label for="repayment_mode" value="Repayment Mode" />
                        <flux:input id="repayment_mode" wire:model.live="repayment_mode"
                            placeholder="Select repayment mode" required />
                        @error('repayment_mode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:label for="repayment_period" value="Repayment Period" />
                        <flux:input id="repayment_period" wire:model.live="repayment_period"
                            placeholder="Select repayment period" required />
                        @error('repayment_period') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Start Date -->
                <div>
                    <flux:label for="start_date" value="Loan Start Date" />
                    <flux:input id="start_date" wire:model="start_date" type="date" required />
                    @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Security/Collateral -->
                <div>
                    <flux:label for="security_description" value="Security/Collateral Description" />
                    <flux:textarea id="security_description" wire:model="security_description"
                        placeholder="Describe any security or collateral provided" />
                    @error('security_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Guarantor Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:label for="guarantor_name" value="Guarantor Name" />
                        <flux:input id="guarantor_name" wire:model="guarantor_name"
                            placeholder="Full name of guarantor" />
                        @error('guarantor_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <flux:label for="guarantor_contact" value="Guarantor Contact" />
                        <flux:input id="guarantor_contact" wire:model="guarantor_contact"
                            placeholder="Phone number or email" />
                        @error('guarantor_contact') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <flux:label for="remarks" value="Additional Remarks" />
                    <flux:textarea id="remarks" wire:model="remarks"
                        placeholder="Any additional information or remarks" />
                    @error('remarks') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <flux:button variant="primary" type="submit">
                        Submit Loan Application
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>