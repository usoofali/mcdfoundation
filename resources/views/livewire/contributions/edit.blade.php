<?php

use App\Models\Contribution;
use App\Models\Member;
use App\Models\ContributionPlan;
use App\Services\ContributionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Edit Contribution'])] class extends Component {
    public Contribution $contribution;

    public $form = [
        'member_id' => '',
        'contribution_plan_id' => '',
        'amount' => '',
        'payment_method' => 'cash',
        'payment_reference' => '',
        'payment_date' => '',
        'status' => 'paid',
        'notes' => '',
    ];

    public $selectedMember = null;
    public $selectedPlan = null;

    public function mount(Contribution $contribution): void
    {
        $this->contribution = $contribution->load(['member', 'contributionPlan']);

        $this->form = [
            'member_id' => $contribution->member_id,
            'contribution_plan_id' => $contribution->contribution_plan_id,
            'amount' => $contribution->amount,
            'payment_method' => $contribution->payment_method,
            'payment_reference' => $contribution->payment_reference,
            'payment_date' => $contribution->payment_date->format('Y-m-d'),
            'status' => $contribution->status,
            'notes' => $contribution->notes,
        ];

        $this->selectedMember = $contribution->member;
        $this->selectedPlan = $contribution->contributionPlan;
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
            'form.status' => 'required|in:paid,pending,overdue,cancelled',
            'form.notes' => 'nullable|string|max:1000',
        ];
    }

    public function update(): void
    {
        $this->validate();

        $contributionService = app(ContributionService::class);

        try {
            $contributionService->updateContribution($this->contribution, $this->form);

            session()->flash('success', 'Contribution updated successfully.');

            $this->redirect(route('contributions.show', $this->contribution));
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to update contribution: ' . $e->getMessage(),
            ]);
        }
    }

    public function updatedFormContributionPlanId($value): void
    {
        if ($value) {
            $this->selectedPlan = ContributionPlan::find($value);
            $this->form['amount'] = $this->selectedPlan->amount;
        }
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
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Contribution</h3>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Update contribution details for
                    {{ $selectedMember->full_name }}</p>
            </div>

            <form wire:submit="update" class="p-6 space-y-6">
                <!-- Member (Read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Member
                    </label>
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ $selectedMember->full_name . ' ' . $selectedMember->family_name }}</div>
                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $selectedMember->registration_no }}</div>
                    </div>
                </div>

                <!-- Contribution Plan -->
                <div>
                    @if($selectedPlan)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Contribution Plan
                            </label>
                            <div
                                class="p-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900/20 dark:border-blue-800">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-blue-900 dark:text-blue-100">{{ $selectedPlan->label }}
                                            Plan</div>
                                        <div class="text-sm text-blue-700 dark:text-blue-300">Amount:
                                            ₦{{ number_format($selectedPlan->amount, 2) }}</div>
                                        @if($selectedPlan->description)
                                            <div class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                                {{ $selectedPlan->description }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @error('form.contribution_plan_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Amount -->
                <div>
                    <flux:input wire:model="form.amount" type="number" step="0.01" label="Amount" placeholder="0.00"
                        required />
                    @error('form.amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <flux:select wire:model="form.payment_method" label="Payment Method" required>
                            @foreach($this->paymentMethodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        @error('form.payment_method')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:input wire:model="form.payment_reference" label="Payment Reference"
                            placeholder="Transaction reference (optional)" />
                        @error('form.payment_reference')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Payment Date -->
                <div>
                    <flux:input wire:model="form.payment_date" type="date" label="Payment Date" required />
                    @error('form.payment_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status and Notes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <flux:select wire:model="form.status" label="Status" placeholder="Select status" required>
                            @foreach($this->statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        @error('form.status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:textarea wire:model="form.notes" label="Notes" placeholder="Additional notes (optional)"
                            rows="3" />
                        @error('form.notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-neutral-200 dark:border-neutral-700">
                    <flux:button type="button" variant="outline"
                        href="{{ route('contributions.show', $contribution) }}">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Update Contribution
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>