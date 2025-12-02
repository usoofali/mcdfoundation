<?php

use App\Models\CashoutRequest;
use App\Services\CashoutService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Request Cashout'])] class extends Component {
    use AuthorizesRequests;

    public $eligibility = [];
    public $reason = '';
    public $amount = 0;

    public function mount(): void
    {
        $this->authorize('create', CashoutRequest::class);

        $member = auth()->user()->member;

        if (!$member) {
            session()->flash('error', 'Member profile not found.');
            $this->redirect(route('dashboard'));
            return;
        }

        $cashoutService = app(CashoutService::class);
        $this->eligibility = $cashoutService->checkCashoutEligibility($member);
        $this->amount = $this->eligibility['eligible_amount'];
    }

    public function submit(): void
    {
        $member = auth()->user()->member;

        if (!$this->eligibility['eligible']) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You are not eligible for cashout.',
            ]);
            return;
        }

        $this->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $cashoutService = app(CashoutService::class);
            $request = $cashoutService->createRequest($member, [
                'reason' => $this->reason,
            ]);

            session()->flash('success', 'Cashout request submitted successfully.');
            $this->redirect(route('cashout.show', $request), navigate: true);
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
                    Request Cashout
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Withdraw your accumulated contributions
                </flux:text>
            </div>
            <div>
                <flux:button variant="ghost" href="{{ route('dashboard') }}" wire:navigate>
                    Back to Dashboard
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Eligibility Status -->
    @if($eligibility['eligible'])
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
            <div class="flex items-start gap-3">
                <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                <div class="flex-1">
                    <flux:heading size="sm" class="font-medium text-green-900 dark:text-green-100">
                        You are Eligible for Cashout
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-green-700 dark:text-green-300">
                        You meet all requirements to request a cashout of your contributions.
                    </flux:text>
                    <div class="mt-3 rounded-lg bg-green-100 dark:bg-green-900 p-3">
                        <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                            ₦{{ number_format($eligibility['eligible_amount'], 2) }}
                        </div>
                        <div class="text-xs text-green-700 dark:text-green-300">Available for cashout</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-start gap-3">
                <flux:icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400" />
                <div class="flex-1">
                    <flux:heading size="sm" class="font-medium text-red-900 dark:text-red-100">
                        Not Eligible for Cashout
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-red-700 dark:text-red-300">
                        You do not currently meet the requirements:
                    </flux:text>
                    <ul class="mt-2 list-disc list-inside text-xs text-red-600 dark:text-red-400 space-y-1">
                        @foreach($eligibility['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Contribution Summary -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
            Contribution Summary
        </flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg bg-neutral-50 dark:bg-neutral-900 p-4">
                <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                    {{ $eligibility['contribution_count'] }}
                </div>
                <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Contributions</div>
            </div>
            <div class="rounded-lg bg-neutral-50 dark:bg-neutral-900 p-4">
                <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                    {{ $eligibility['membership_months'] }}
                </div>
                <div class="text-xs text-neutral-500 dark:text-neutral-400">Months as Member</div>
            </div>
            <div class="rounded-lg bg-neutral-50 dark:bg-neutral-900 p-4">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    ₦{{ number_format($eligibility['total_contributions'] + $eligibility['total_fines_paid'], 2) }}
                </div>
                <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Paid</div>
            </div>
        </div>
    </div>

    @if($eligibility['eligible'])
        <!-- Request Form -->
        <form wire:submit="submit">
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                            Cashout Request Details
                        </flux:heading>

                        <div class="space-y-4">
                            <div>
                                <flux:label>Cashout Amount</flux:label>
                                <flux:input value="₦{{ number_format($amount, 2) }}" disabled />
                                <flux:text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                    This includes all your contributions and fines paid
                                </flux:text>
                            </div>

                            <div>
                                <flux:label>Reason for Cashout (Optional)</flux:label>
                                <flux:textarea wire:model="reason" rows="3"
                                    placeholder="Please provide a reason for requesting cashout..." />
                                @error('reason') <flux:error>{{ $message }}</flux:error> @enderror
                            </div>

                            <!-- Bank Details Confirmation -->
                            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4">
                                <flux:heading size="sm" class="mb-2 text-blue-900 dark:text-blue-100">
                                    Payment will be sent to:
                                </flux:heading>
                                <div class="text-sm space-y-1">
                                    <div><span class="font-medium">Bank:</span> {{ auth()->user()->bank_name ?? 'Not set' }}
                                    </div>
                                    <div><span class="font-medium">Account:</span>
                                        {{ auth()->user()->account_number ?? 'Not set' }}</div>
                                    <div><span class="font-medium">Name:</span>
                                        {{ auth()->user()->account_name ?? 'Not set' }}</div>
                                </div>
                                @if(!auth()->user()->account_number)
                                    <div class="mt-2 text-xs text-red-600 dark:text-red-400">
                                        Please update your bank details in your profile before submitting.
                                    </div>
                                @endif
                            </div>

                            <!-- Warning -->
                            <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-4">
                                <div class="flex items-start gap-3">
                                    <flux:icon name="exclamation-triangle"
                                        class="size-5 text-yellow-600 dark:text-yellow-400" />
                                    <div>
                                        <flux:heading size="sm" class="font-medium text-yellow-900 dark:text-yellow-100">
                                            Important Notice
                                        </flux:heading>
                                        <flux:text class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                            After cashout, you will need to rebuild your eligibility for health claims,
                                            loans, and programs by making new contributions.
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-neutral-200 dark:border-neutral-700 pt-6">
                        <flux:button variant="ghost" href="{{ route('dashboard') }}" wire:navigate>
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" :disabled="!auth()->user()->account_number">
                            Submit Cashout Request
                        </flux:button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>