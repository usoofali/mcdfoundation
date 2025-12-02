<?php

use App\Models\CashoutRequest;
use App\Services\CashoutService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'My Cashout Requests'])] class extends Component {
    use AuthorizesRequests;

    public function getRequestsProperty()
    {
        $member = auth()->user()->member;

        if (!$member) {
            return collect();
        }

        $cashoutService = app(CashoutService::class);
        return $cashoutService->getMemberCashoutHistory($member);
    }

    public function getEligibilityProperty()
    {
        $member = auth()->user()->member;

        if (!$member) {
            return ['eligible' => false, 'reasons' => ['Member profile not found']];
        }

        $cashoutService = app(CashoutService::class);
        return $cashoutService->checkCashoutEligibility($member);
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    My Cashout Requests
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    View your cashout request history and current eligibility
                </flux:text>
            </div>
            @if($this->eligibility['eligible'])
                <div>
                    <flux:button variant="primary" href="{{ route('cashout.create') }}" wire:navigate icon="plus">
                        New Cashout Request
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    <!-- Eligibility Card -->
    @if($this->eligibility['eligible'])
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
            <div class="flex items-start gap-3">
                <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                <div class="flex-1">
                    <flux:heading size="sm" class="font-medium text-green-900 dark:text-green-100">
                        You are Eligible for Cashout
                    </flux:heading>
                    <div class="mt-2 text-2xl font-bold text-green-900 dark:text-green-100">
                        ₦{{ number_format($this->eligibility['eligible_amount'], 2) }}
                    </div>
                    <div class="text-xs text-green-700 dark:text-green-300">Available for cashout</div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
            <div class="flex items-start gap-3">
                <flux:icon name="information-circle" class="size-5 text-yellow-600 dark:text-yellow-400" />
                <div class="flex-1">
                    <flux:heading size="sm" class="font-medium text-yellow-900 dark:text-yellow-100">
                        Not Currently Eligible
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        You don't currently meet the requirements for cashout.
                    </flux:text>
                </div>
            </div>
        </div>
    @endif

    <!-- Requests List -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        @if($this->requests->count() > 0)
            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @foreach($this->requests as $request)
                    <div class="p-4 sm:p-6 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors cursor-pointer"
                        wire:click="$navigate('{{ route('cashout.show', $request) }}')">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <flux:heading size="sm" class="font-medium text-neutral-900 dark:text-white">
                                        Request #{{ $request->id }}
                                    </flux:heading>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                @if($request->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @elseif($request->status === 'verified') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @elseif($request->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($request->status === 'disbursed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @endif">
                                        {{ $request->status_label }}
                                    </span>
                                </div>
                                <div class="text-sm text-neutral-600 dark:text-neutral-400">
                                    Requested: {{ $request->created_at->format('M d, Y') }}
                                </div>
                                @if($request->disbursed_at)
                                    <div class="text-sm text-green-600 dark:text-green-400">
                                        Disbursed: {{ $request->disbursed_at->format('M d, Y') }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-neutral-900 dark:text-white">
                                    ₦{{ number_format($request->requested_amount, 2) }}
                                </div>
                                @if($request->approved_amount && $request->approved_amount != $request->requested_amount)
                                    <div class="text-sm text-green-600 dark:text-green-400">
                                        Approved: ₦{{ number_format($request->approved_amount, 2) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon name="banknotes" class="mx-auto size-12 text-neutral-400" />
                <flux:heading size="sm" class="mt-4 font-medium text-neutral-900 dark:text-white">
                    No Cashout Requests Yet
                </flux:heading>
                <flux:text class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    You haven't submitted any cashout requests yet.
                </flux:text>
                @if($this->eligibility['eligible'])
                    <div class="mt-6">
                        <flux:button variant="primary" href="{{ route('cashout.create') }}" wire:navigate>
                            Submit Your First Request
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>