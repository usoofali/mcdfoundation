<?php

use App\Models\HealthClaim;
use App\Models\Member;
use App\Models\HealthcareProvider;
use App\Services\HealthClaimService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Health Claims'])] class extends Component {
    use AuthorizesRequests, WithPagination;

    public $search = '';
    public $status = '';
    public $claim_type = '';
    public $member_id = '';
    public $healthcare_provider_id = '';
    public $date_from = '';
    public $date_to = '';
    public $perPage = 15;

    public function mount(): void
    {
        $this->authorize('viewAny', HealthClaim::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedClaimType(): void
    {
        $this->resetPage();
    }

    public function updatedMemberId(): void
    {
        $this->resetPage();
    }

    public function updatedHealthcareProviderId(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->claim_type = '';
        $this->member_id = '';
        $this->healthcare_provider_id = '';
        $this->date_from = '';
        $this->date_to = '';
        $this->resetPage();
    }

    public function getClaimsProperty()
    {
        $claimService = app(HealthClaimService::class);

        $filters = [
            'search' => $this->search,
            'status' => $this->status,
            'claim_type' => $this->claim_type,
            'member_id' => $this->member_id,
            'healthcare_provider_id' => $this->healthcare_provider_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        return $claimService->getClaims($filters, $this->perPage);
    }

    public function getStatsProperty()
    {
        $claimService = app(HealthClaimService::class);

        $filters = [
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        return $claimService->getClaimStats($filters);
    }

    public function getMembersProperty()
    {
        return Member::where('status', 'active')->orderBy('full_name')->get();
    }

    public function getHealthcareProvidersProperty()
    {
        return HealthcareProvider::orderBy('name')->get();
    }

    public function getStatusOptionsProperty()
    {
        return [
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'paid' => 'Paid',
        ];
    }

    public function getClaimTypeOptionsProperty()
    {
        return [
            'outpatient' => 'Outpatient',
            'inpatient' => 'Inpatient',
            'surgery' => 'Surgery',
            'maternity' => 'Maternity',
        ];
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Health Claims
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Manage member health claims and track reimbursements
                </flux:text>
            </div>
            @can('create', HealthClaim::class)
                <div>
                    <flux:button variant="primary" icon="plus" variant="primary" href="{{ route('health-claims.create') }}"
                        class="gap-2">
                        Submit Claim
                    </flux:button>
                </div>
            @endcan
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 p-2 sm:p-3 dark:bg-blue-900/20">
                    <flux:icon name="document-text" class="size-5 sm:size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Total Claims
                    </flux:text>
                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                        {{ $this->stats['total_claims'] }}
                    </flux:heading>
                </div>
            </div>
        </div>
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-yellow-100 p-2 sm:p-3 dark:bg-yellow-900/20">
                    <flux:icon name="clock" class="size-5 sm:size-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Submitted
                    </flux:text>
                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                        {{ $this->stats['submitted_claims'] }}
                    </flux:heading>
                </div>
            </div>
        </div>
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-100 p-2 sm:p-3 dark:bg-green-900/20">
                    <flux:icon name="check-badge" class="size-5 sm:size-6 text-green-600 dark:text-green-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Paid
                    </flux:text>
                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                        {{ $this->stats['paid_claims'] }}
                    </flux:heading>
                </div>
            </div>
        </div>
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-purple-100 p-2 sm:p-3 dark:bg-purple-900/20">
                    <flux:icon name="banknotes" class="size-5 sm:size-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Total Covered
                    </flux:text>
                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                        ₦{{ number_format($this->stats['total_covered_amount'], 2) }}
                    </flux:heading>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="sm" class="font-medium text-neutral-900 dark:text-white">
                Filters
            </flux:heading>
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                Clear Filters
            </flux:button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <flux:input wire:model.live="search" placeholder="Search by claim number or member name"
                    icon="magnifying-glass" />
            </div>

            <div>
                <flux:select wire:model.live="status" placeholder="Filter by status">
                    <option value="">All Statuses</option>
                    @foreach($this->statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="claim_type" placeholder="Filter by claim type">
                    <option value="">All Claim Types</option>
                    @foreach($this->claimTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <flux:select wire:model.live="healthcare_provider_id" placeholder="Filter by provider">
                    <option value="">All Providers</option>
                    @foreach($this->healthcareProviders as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:input wire:model.live="date_from" type="date" placeholder="From date" />
            </div>

            <div>
                <flux:input wire:model.live="date_to" type="date" placeholder="To date" />
            </div>
        </div>
    </div>

    <!-- Claims Table -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        @if($this->claims->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Claim #</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Member</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Type</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Provider</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Billed</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Covered</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                        @foreach($this->claims as $claim)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $claim->claim_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div>{{ $claim->member->full_name }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $claim->member->registration_no }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $claim->claim_type_label }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $claim->healthcareProvider->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    ₦{{ number_format($claim->billed_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div>₦{{ number_format($claim->covered_amount, 2) }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $claim->coverage_percent }}%
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        @if($claim->status === 'submitted') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                        @elseif($claim->status === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                        @elseif($claim->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                        @elseif($claim->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                        @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                        @endif">
                                        {{ $claim->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $claim->claim_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:button variant="ghost" size="sm" href="{{ route('health-claims.show', $claim) }}">
                                            View
                                        </flux:button>
                                        @if($claim->status === 'submitted')
                                            <flux:button variant="ghost" size="sm" href="{{ route('health-claims.edit', $claim) }}">
                                                Edit
                                            </flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                {{ $this->claims->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                    No health claims
                </flux:heading>
                <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Get started by submitting a health claim.
                </flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" icon="plus" variant="primary" href="{{ route('health-claims.create') }}"
                        class="gap-2">
                        Submit Claim
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</div>