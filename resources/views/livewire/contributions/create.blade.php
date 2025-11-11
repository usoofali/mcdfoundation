<?php

use App\Models\Member;
use App\Models\ContributionPlan;
use App\Services\ContributionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Create Contribution'])] class extends Component {
    public $form = [
        'member_id' => '',
        'contribution_plan_id' => '',
        'amount' => '',
        'payment_method' => 'cash',
        'payment_reference' => '',
        'payment_date' => '',
        'period_start' => '',
        'period_end' => '',
        'status' => 'paid',
        'notes' => '',
    ];

    public $selectedMember = null;
    public $selectedPlan = null;
    public $showMemberSearch = false;
    public $memberSearch = '';

    public function mount(): void
    {
        $this->form['payment_date'] = now()->format('Y-m-d');
        $this->form['period_start'] = now()->format('Y-m-d');
        $this->form['period_end'] = now()->addMonth()->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'form.member_id' => 'required|exists:members,id',
            'form.contribution_plan_id' => 'required|exists:contribution_plans,id',
            'form.amount' => 'required|numeric|min:0.01',
            'form.payment_method' => 'required|in:cash,transfer,bank_deposit,mobile_money',
            'form.payment_reference' => 'nullable|string|max:255',
            'form.payment_date' => 'required|date|before_or_equal:today',
            'form.period_start' => 'required|date',
            'form.period_end' => 'required|date|after_or_equal:form.period_start',
            'form.status' => 'required|in:paid,pending,overdue,cancelled',
            'form.notes' => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $contributionService = app(ContributionService::class);

        try {
            $data = $this->form;
            $data['collected_by'] = Auth::id();

            $contribution = $contributionService->recordContribution($data);

            session()->flash('success', 'Contribution recorded successfully. Receipt: ' . $contribution->receipt_number);
            
            $this->redirect(route('contributions.index'));
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to record contribution: ' . $e->getMessage());
        }
    }

    public function updatedFormMemberId($value): void
    {
        if ($value) {
            $this->selectedMember = Member::find($value);
            $this->showMemberSearch = false;
        }
    }

    public function updatedFormContributionPlanId($value): void
    {
        if ($value) {
            $this->selectedPlan = ContributionPlan::find($value);
            $this->form['amount'] = $this->selectedPlan->amount;
        }
    }

    public function updatedMemberSearch(): void
    {
        $this->showMemberSearch = $this->memberSearch !== '';
    }

    public function selectMember($memberId): void
    {
        $this->form['member_id'] = $memberId;
        $this->selectedMember = Member::find($memberId);
        $this->memberSearch = '';
        $this->showMemberSearch = false;
    }

    public function getMembersProperty()
    {
        if (empty($this->memberSearch)) {
            return collect();
        }

        return Member::where('full_name', 'like', "%{$this->memberSearch}%")
            ->orWhere('registration_no', 'like', "%{$this->memberSearch}%")
            ->limit(10)
            ->get();
    }

    public function getContributionPlansProperty()
    {
        return ContributionPlan::where('is_active', true)->orderBy('name')->get();
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

    public function getStatusOptionsProperty()
    {
        return [
            'paid' => 'Paid',
            'pending' => 'Pending',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ];
    }
}; ?>

<div>
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Record Contribution</h3>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Record a new member contribution</p>
            </div>

            <form wire:submit="save" class="p-6 space-y-6">
                <!-- Member Selection -->
                <div>
                    <flux:input 
                        wire:model.live="memberSearch" 
                        label="Search Member"
                        placeholder="Type member name or registration number"
                        icon="magnifying-glass"
                    />
                    
                    @if($showMemberSearch && $this->members->count() > 0)
                        <div class="mt-2 border border-neutral-200 dark:border-neutral-700 rounded-lg shadow-lg bg-white dark:bg-zinc-800 max-h-60 overflow-y-auto">
                            @foreach($this->members as $member)
                                <div class="px-4 py-3 hover:bg-neutral-50 dark:hover:bg-neutral-700 cursor-pointer border-b border-neutral-100 dark:border-neutral-600 last:border-b-0"
                                     wire:click="selectMember({{ $member->id }})">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $member->full_name }}</div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ $member->registration_no }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedMember)
                        <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <div class="font-medium text-green-800">{{ $selectedMember->full_name }}</div>
                                    <div class="text-sm text-green-600">{{ $selectedMember->registration_no }}</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @error('form.member_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contribution Plan -->
                <div>
                    <flux:input 
                        wire:model.live="form.contribution_plan_id" 
                        label="Contribution Plan"
                        placeholder="Select contribution plan"
                        required
                    />
                    
                    @if($selectedPlan)
                        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="font-medium text-blue-800">{{ $selectedPlan->label }} Plan</div>
                            <div class="text-sm text-blue-600">Amount: ₦{{ number_format($selectedPlan->amount, 2) }}</div>
                            @if($selectedPlan->description)
                                <div class="text-sm text-blue-600 mt-1">{{ $selectedPlan->description }}</div>
                            @endif
                        </div>
                    @endif

                    @error('form.contribution_plan_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Amount -->
                <div>
                    <flux:input 
                        wire:model="form.amount" 
                        type="number"
                        step="0.01"
                        label="Amount"
                        placeholder="0.00"
                        required
                    />
                    @error('form.amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <flux:input 
                            wire:model="form.payment_method" 
                            label="Payment Method"
                            placeholder="Select payment method"
                            required
                        />
                        @error('form.payment_method')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:input 
                            wire:model="form.payment_reference" 
                            label="Payment Reference"
                            placeholder="Transaction reference (optional)"
                        />
                        @error('form.payment_reference')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <flux:input 
                            wire:model="form.payment_date" 
                            type="date"
                            label="Payment Date"
                            required
                        />
                        @error('form.payment_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:input 
                            wire:model="form.period_start" 
                            type="date"
                            label="Period Start"
                            required
                        />
                        @error('form.period_start')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:input 
                            wire:model="form.period_end" 
                            type="date"
                            label="Period End"
                            required
                        />
                        @error('form.period_end')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Status and Notes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <flux:input 
                            wire:model="form.status" 
                            label="Status"
                            placeholder="Select status"
                            required
                        />
                        @error('form.status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:textarea 
                            wire:model="form.notes" 
                            label="Notes"
                            placeholder="Additional notes (optional)"
                            rows="3"
                        />
                        @error('form.notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-neutral-200 dark:border-neutral-700">
                    <flux:button type="button" variant="outline" href="{{ route('contributions.index') }}">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Record Contribution
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
