<?php

use App\Models\CashoutRequest;
use App\Services\CashoutService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Cashout Requests'])] class extends Component {
    use AuthorizesRequests, WithPagination;

    public $search = '';
    public $statusFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', CashoutRequest::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function getRequestsProperty()
    {
        $cashoutService = app(CashoutService::class);

        $filters = [];
        if ($this->search) {
            $filters['search'] = $this->search;
        }
        if ($this->statusFilter !== 'all') {
            $filters['status'] = $this->statusFilter;
        }

        return $cashoutService->getCashoutRequests($filters);
    }

    public function getStatsProperty()
    {
        $cashoutService = app(CashoutService::class);
        return $cashoutService->getCashoutStats();
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Cashout Requests
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Manage member cashout requests and disbursements
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->stats['pending_requests'] }}
            </div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">Pending</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->stats['verified_requests'] }}
            </div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">Verified</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->stats['approved_requests'] }}
            </div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">Awaiting Disbursement</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                ₦{{ number_format($this->stats['total_disbursed'], 0) }}
            </div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Disbursed</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <flux:input wire:model.live="search" placeholder="Search by member name or reference..."
                    icon="magnifying-glass" />
            </div>
            <div>
                <flux:select wire:model.live="statusFilter">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="verified">Verified</option>
                    <option value="approved">Approved</option>
                    <option value="disbursed">Disbursed</option>
                    <option value="rejected">Rejected</option>
                </flux:select>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        @if($this->requests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                                Member
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                                Amount
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                                Requested
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach($this->requests as $request)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-neutral-900 dark:text-white">{{ $request->member->full_name }}
                                    </div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $request->member->registration_no }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-neutral-900 dark:text-white">
                                        ₦{{ number_format($request->requested_amount, 2) }}
                                    </div>
                                    @if($request->approved_amount && $request->approved_amount != $request->requested_amount)
                                        <div class="text-xs text-green-600 dark:text-green-400">
                                            Approved: ₦{{ number_format($request->approved_amount, 2) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $request->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                @if($request->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @elseif($request->status === 'verified') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @elseif($request->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($request->status === 'disbursed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @endif">
                                        {{ $request->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <flux:button variant="ghost" size="sm" href="{{ route('admin.cashout.show', $request) }}"
                                        wire:navigate>
                                        View
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                {{ $this->requests->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                    No cashout requests found
                </flux:heading>
                <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Cashout requests will appear here when members submit them.
                </flux:text>
            </div>
        @endif
    </div>
</div>