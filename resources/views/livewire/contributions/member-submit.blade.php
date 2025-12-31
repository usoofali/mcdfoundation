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
    ];

    public $receipt;
    public $selectedPlan = null;

    // New properties for expected contributions
    public $paymentMode = 'expected'; // 'expected' or 'custom'
    public $expectedContributions = [];
    public $selectedExpectedContributions = [];

    public function mount(): void
    {
        $this->form['payment_date'] = now()->format('Y-m-d');

        // Pre-select plan from member profile
        $member = auth()->user()->member;
        if ($member->contribution_plan_id) {
            $this->form['contribution_plan_id'] = $member->contribution_plan_id;
            $this->updatedFormContributionPlanId($member->contribution_plan_id);
        }

        $this->loadExpectedContributions();
    }

    public function loadExpectedContributions(): void
    {
        $member = auth()->user()->member;

        // Force update of overdue status and fines
        $expectedService = app(\App\Services\ExpectedContributionService::class);
        $expectedService->updateMemberOverdueStatus($member);

        // Fetch unpaid expected contributions (pending or overdue)
        // Exclude those that already have a pending payment awaiting verification
        $this->expectedContributions = \App\Models\ExpectedContribution::where('member_id', $member->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->whereDoesntHave('actualContribution', function ($q) {
                $q->where('status', 'pending');
            })
            ->orderBy('period_start', 'asc')
            ->get();

        // If no expected contributions, default to custom mode
        if ($this->expectedContributions->isEmpty()) {
            $this->paymentMode = 'custom';
        }
    }

    public function updatedPaymentMode($value): void
    {
        if ($value === 'expected') {
            $this->loadExpectedContributions();
            $this->selectedExpectedContributions = [];
            $this->resetFormForExpected();
        }
    }

    public function updatedSelectedExpectedContributions(): void
    {
        if (empty($this->selectedExpectedContributions)) {
            $this->resetFormForExpected();
            return;
        }

        $selectedIds = $this->selectedExpectedContributions;
        $selectedItems = $this->expectedContributions->whereIn('id', $selectedIds);

        if ($selectedItems->isEmpty()) {
            return;
        }

        // Calculate total amount (expected + fines)
        $totalAmount = $selectedItems->sum(function ($item) {
            return $item->expected_amount + $item->fine_amount;
        });

        // Determine period range
        $minDate = $selectedItems->min('period_start');
        $maxDate = $selectedItems->max('period_end');

        // Get plan from first item (assuming all should be same plan ideally, or just pick first)
        $firstItem = $selectedItems->first();

        $this->form['amount'] = $totalAmount;
        $this->form['contribution_plan_id'] = $firstItem->contribution_plan_id;

        // Load plan details for display
        $this->updatedFormContributionPlanId($firstItem->contribution_plan_id);
    }

    protected function resetFormForExpected(): void
    {
        $this->form['amount'] = '';
        $this->form['contribution_plan_id'] = '';
        $this->selectedPlan = null;
    }

    public function rules(): array
    {
        return [
            'form.contribution_plan_id' => 'required|exists:contribution_plans,id',
            'form.amount' => 'required|numeric|min:0.01',
            'form.payment_method' => 'required|in:transfer,bank_deposit,mobile_money',
            'form.payment_reference' => 'required|string|max:255',
            'form.payment_date' => 'required|date|before_or_equal:today',
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

            // Pass selected expected contribution IDs if in expected mode
            $expectedIds = ($this->paymentMode === 'expected') ? $this->selectedExpectedContributions : [];

            $contribution = $contributionService->submitMemberContribution($data, $this->receipt, $expectedIds);

            session()->flash('success', 'Contribution submitted successfully! Receipt: ' . $contribution->receipt_number . '. Please wait for staff verification.');

            $this->redirect(route('my.contributions'));
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

            // Only auto-fill amount if in custom mode or if amount is empty
            if ($this->paymentMode === 'custom' || empty($this->form['amount'])) {
                $this->form['amount'] = $this->selectedPlan->amount;
                $this->calculatePeriod();
            }
        }
    }

    public function updatedFormPaymentMethod($value): void
    {
        // Reset payment reference when method changes
        $this->form['payment_reference'] = '';
    }

    protected function calculatePeriod(): void
    {
        if (!$this->selectedPlan || $this->paymentMode === 'expected') {
            return;
        }

        $startDate = now();
        $endDate = match ($this->selectedPlan->frequency) {
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
                <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">Upload your payment receipt for verification
                </p>
            </div>

            <form wire:submit="submit" class="p-6 space-y-6">
                <!-- Payment Mode Toggle -->
                <div class="flex rounded-md shadow-sm" role="group">
                    <button type="button" wire:click="$set('paymentMode', 'expected')"
                        class="px-4 py-2 text-sm font-medium border rounded-l-lg {{ $paymentMode === 'expected' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-zinc-800 dark:text-white dark:border-zinc-700' }}">
                        Pay Outstanding/Due
                    </button>
                    <button type="button" wire:click="$set('paymentMode', 'custom')"
                        class="px-4 py-2 text-sm font-medium border rounded-r-lg {{ $paymentMode === 'custom' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-zinc-800 dark:text-white dark:border-zinc-700' }}">
                        Custom Payment
                    </button>
                </div>

                @if($paymentMode === 'expected')
                    <!-- Expected Contributions List -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Select Contributions to Pay</h4>

                        @if($expectedContributions->count() > 0)
                            <div class="border rounded-lg overflow-x-auto dark:border-zinc-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                    <thead class="bg-gray-50 dark:bg-zinc-800">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Select
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Period
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Amount
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-zinc-900 dark:divide-zinc-700">
                                        @foreach($expectedContributions as $expected)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="checkbox" wire:model.live="selectedExpectedContributions"
                                                        value="{{ $expected->id }}"
                                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {{ $expected->period_start->format('d M, Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-xs sm:text-sm">
                                                    <span
                                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                                                                                                                                                                                    {{ $expected->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                        {{ ucfirst($expected->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    ₦{{ number_format($expected->expected_amount + $expected->fine_amount, 2) }}
                                                    @if($expected->fine_amount > 0)
                                                        <span class="text-xs text-red-500 block">(Inc. Fine:
                                                            ₦{{ number_format($expected->fine_amount) }})</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 bg-gray-50 rounded-lg dark:bg-zinc-800">
                                <p class="text-gray-500 dark:text-zinc-400">No outstanding contributions found.</p>
                                <button type="button" wire:click="$set('paymentMode', 'custom')"
                                    class="mt-2 text-sm text-blue-600 hover:text-blue-500">
                                    Make a custom payment instead
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- Contribution Plan Selection (Custom Mode) -->
                    <div>
                        @if($this->selectedPlan)
                            <!-- Read-only Plan Display -->
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 border border-gray-200 dark:border-zinc-700">
                                <flux:label>Contribution Plan</flux:label>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $this->selectedPlan->label }} - ₦{{ number_format($this->selectedPlan->amount) }}
                                    ({{ ucfirst($this->selectedPlan->frequency) }})
                                </div>
                                <input type="hidden" wire:model="form.contribution_plan_id">
                            </div>
                        @else
                            @if($this->contributionPlans->count() > 0)
                                <flux:select wire:model.live="form.contribution_plan_id" label="Contribution Plan"
                                    placeholder="Select a plan..." required>
                                    @foreach($this->contributionPlans as $plan)
                                        <option value="{{ $plan->id }}">
                                            {{ $plan->label }} - ₦{{ number_format($plan->amount) }} ({{ ucfirst($plan->frequency) }})
                                        </option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:input label="Contribution Plan" placeholder="No contribution plans available" disabled />
                            @endif
                            @error('form.contribution_plan_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                @endif

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
                    <flux:input wire:model="form.amount" label="Amount (₦)" type="number" step="0.01" min="0.01"
                        required disabled />
                    @error('form.amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Method -->
                <div>
                    <flux:select wire:model.live="form.payment_method" label="Payment Method"
                        placeholder="Select payment method..." required>
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
                    <flux:input wire:model="form.payment_reference" label="Payment Reference"
                        placeholder="Transaction reference number" required />
                    @error('form.payment_reference')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Enter the transaction reference from your bank or mobile money
                        app</p>
                </div>

                <!-- Payment Date -->
                <div>
                    <flux:input wire:model="form.payment_date" label="Payment Date" type="date" required />
                    @error('form.payment_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Period Coverage - REMOVED -->
                <!-- Period dates are now tracked via linked Expected Contributions or simply by Payment Date -->

                <!-- Receipt Upload -->
                <div>
                    <flux:input wire:model="receipt" label="Payment Receipt" type="file" accept="image/*,.pdf"
                        required />
                    @error('receipt')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Upload a clear photo or PDF of your payment receipt (max 2MB)
                    </p>

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
                    <flux:button type="button" variant="outline" href="{{ route('my.contributions') }}">
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