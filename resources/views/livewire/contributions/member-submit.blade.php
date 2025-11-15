<?php

use App\Models\ContributionPlan;
use App\Services\ContributionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app', ['title' => 'Submit Contribution'])] class extends Component {
    use WithFileUploads;

    public $form = [
        'contribution_plan_id' => '',
        'amount' => '',
        'payment_method' => 'transfer',
        'payment_reference' => '',
        'payment_date' => '',
        'period_start' => '',
        'period_end' => '',
    ];

    public $receipt;
    public $selectedPlan = null;

    public function mount(): void
    {
        $this->form['payment_date'] = now()->format('Y-m-d');
        $this->form['period_start'] = now()->format('Y-m-d');
        $this->form['period_end'] = now()->addMonth()->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'form.contribution_plan_id' => 'required|exists:contribution_plans,id',
            'form.amount' => 'required|numeric|min:0.01',
            'form.payment_method' => 'required|in:transfer,bank_deposit,mobile_money',
            'form.payment_reference' => 'required|string|max:255',
            'form.payment_date' => 'required|date|before_or_equal:today',
            'form.period_start' => 'required|date',
            'form.period_end' => 'required|date|after_or_equal:form.period_start',
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $contributionService = app(ContributionService::class);

        try {
            $data = $this->form;
            $data['member_id'] = auth()->user()->member->id;

            $contribution = $contributionService->submitMemberContribution($data, $this->receipt);

            session()->flash('success', 'Contribution submitted successfully! Receipt: ' . $contribution->receipt_number . '. Please wait for staff verification.');
            
            $this->redirect(route('contributions.index'));
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to submit contribution: ' . $e->getMessage(),
            ]);
        }
    }

    public function updatedFormContributionPlanId($value): void
    {
        if ($value) {
            $this->selectedPlan = ContributionPlan::find($value);
            $this->form['amount'] = $this->selectedPlan->amount;
            
            // Auto-calculate period based on plan type
            $this->calculatePeriod();
        }
    }

    public function updatedFormPaymentMethod($value): void
    {
        // Reset payment reference when method changes
        $this->form['payment_reference'] = '';
    }

    protected function calculatePeriod(): void
    {
        if (!$this->selectedPlan) {
            return;
        }

        $startDate = now();
        $endDate = match($this->selectedPlan->frequency) {
            'daily' => $startDate->copy()->addDay(),
            'weekly' => $startDate->copy()->addWeek(),
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'annual' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };

        $this->form['period_start'] = $startDate->format('Y-m-d');
        $this->form['period_end'] = $endDate->format('Y-m-d');
    }

    public function getContributionPlansProperty()
    {
        return ContributionPlan::where('is_active', true)->orderBy('amount')->get();
    }

    public function getPaymentMethodOptionsProperty()
    {
        return [
            'transfer' => 'Bank Transfer',
            'bank_deposit' => 'Bank Deposit',
            'mobile_money' => 'Mobile Money',
        ];
    }
}; ?>

<div>
<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
             <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
                 <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                     <h3 class="text-lg font-medium text-gray-900 dark:text-white">Submit Contribution</h3>
                     <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">Upload your payment receipt for verification</p>
                </div>

                <form wire:submit="submit" class="p-6 space-y-6">
                    <!-- Contribution Plan Selection -->
                    <div>
                        @if($this->contributionPlans->count() > 0)
                            <flux:select
                                wire:model.live="form.contribution_plan_id"
                                label="Contribution Plan"
                                placeholder="Select a plan..."
                                required
                            >
                                @foreach($this->contributionPlans as $plan)
                                    <option value="{{ $plan->id }}">
                                    {{ $plan->label }} - ₦{{ number_format($plan->amount) }} ({{ ucfirst($plan->frequency) }})
                                    </option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:input
                                label="Contribution Plan"
                                placeholder="No contribution plans available"
                                disabled
                            />
                        @endif
                        @error('form.contribution_plan_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($selectedPlan)
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <h4 class="text-sm font-medium text-blue-800">Selected Plan Details</h4>
                            <div class="mt-2 text-sm text-blue-700">
                                <p><strong>Plan:</strong> {{ $selectedPlan->label }}</p>
                                <p><strong>Amount:</strong> ₦{{ number_format($selectedPlan->amount) }}</p>
                                <p><strong>Frequency:</strong> {{ ucfirst($selectedPlan->frequency) }}</p>
                                @if($selectedPlan->description)
                                    <p><strong>Description:</strong> {{ $selectedPlan->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Amount -->
                    <div>
                        <flux:input 
                            wire:model="form.amount" 
                            label="Amount (₦)"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                        />
                        @error('form.amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <flux:select
                            wire:model.live="form.payment_method"
                            label="Payment Method"
                            placeholder="Select payment method..."
                            required
                        >
                            @foreach($this->paymentMethodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        @error('form.payment_method')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Reference -->
                    <div>
                        <flux:input 
                            wire:model="form.payment_reference" 
                            label="Payment Reference"
                            placeholder="Transaction reference number"
                            required
                        />
                        @error('form.payment_reference')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Enter the transaction reference from your bank or mobile money app</p>
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <flux:input 
                            wire:model="form.payment_date" 
                            label="Payment Date"
                            type="date"
                            required
                        />
                        @error('form.payment_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Period Coverage -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <flux:input 
                                wire:model="form.period_start" 
                                label="Period Start"
                                type="date"
                                required
                            />
                            @error('form.period_start')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input 
                                wire:model="form.period_end" 
                                label="Period End"
                                type="date"
                                required
                            />
                            @error('form.period_end')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Receipt Upload -->
                    <div>
                        <flux:input 
                            wire:model="receipt" 
                            label="Payment Receipt"
                            type="file"
                            accept="image/*,.pdf"
                            required
                        />
                        @error('receipt')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Upload a clear photo or PDF of your payment receipt (max 2MB)</p>
                        
                        @if($receipt)
                            <div class="mt-3">
                                <div class="flex items-center space-x-2 text-sm text-green-600">
                                    <flux:icon name="check-circle" class="w-4 h-4" />
                                    <span>Receipt uploaded: {{ $receipt->getClientOriginalName() }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Important Notice -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <flux:icon name="exclamation-triangle" class="w-5 h-5 text-yellow-400" />
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Important Notice</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Your contribution will be reviewed by staff before approval</li>
                                        <li>Ensure your payment reference is correct and matches your receipt</li>
                                        <li>You will be notified once your contribution is verified</li>
                                        <li>Only clear, readable receipts will be accepted</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3">
                        <flux:button type="button" variant="outline" href="{{ route('contributions.index') }}">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Submit Contribution</span>
                            <span wire:loading>Submitting...</span>
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
</div>
