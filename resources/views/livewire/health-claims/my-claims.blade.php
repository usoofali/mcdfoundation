<?php

use App\Models\HealthClaim;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'My Health Claims'])] class extends Component {
    use WithPagination;

    public $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $member = auth()->user()->member;

        $query = $member->healthClaims()
            ->with(['healthcareProvider'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy('claim_date', 'desc');

        return [
            'claims' => $query->paginate(10),
            'stats' => [
                'total' => $member->healthClaims()->count(),
                'pending' => $member->healthClaims()->where('status', 'submitted')->count(),
                'approved' => $member->healthClaims()->where('status', 'approved')->count(),
                'total_billed' => $member->healthClaims()->whereIn('status', ['approved', 'paid'])->sum('billed_amount'),
                'total_covered' => $member->healthClaims()->whereIn('status', ['approved', 'paid'])->sum('covered_amount'),
            ],
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('My Health Claims') }}</h2>
        <flux:button :href="route('my.claims.submit')" variant="primary" wire:navigate>
            {{ __('Submit Claim') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Claims') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Pending') }}</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['pending'] }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Billed') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    ₦{{ number_format($stats['total_billed'], 2) }}
                </p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Covered') }}</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                    ₦{{ number_format($stats['total_covered'], 2) }}
                </p>
            </div>
        </div>

        <!-- Claims List -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700">
            <!-- Filter -->
            <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
                <flux:select wire:model.live="statusFilter" placeholder="All Status" class="w-48">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="submitted">{{ __('Submitted') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="paid">{{ __('Paid') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                </flux:select>
            </div>

            <!-- Claims -->
            @if($claims->count() > 0)
                <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @foreach($claims as $claim)
                            <div class="p-6 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $claim->claim_number }}
                                            </h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        {{ $claim->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                        ($claim->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                            ($claim->status === 'submitted' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200')) }}">
                                                {{ $claim->status_label }}
                                            </span>
                                        </div>

                                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Provider') }}</p>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $claim->healthcareProvider->name }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Type') }}</p>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $claim->claim_type_label }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Billed Amount') }}
                                                </p>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    ₦{{ number_format($claim->billed_amount, 2) }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Covered Amount') }}
                                                </p>
                                                <p class="text-sm font-medium text-green-600 dark:text-green-400">
                                                    ₦{{ number_format($claim->covered_amount, 2) }}
                                                </p>
                                            </div>
                                        </div>

                                        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                            <span class="font-medium">{{ __('Claim Date:') }}</span>
                                            {{ $claim->claim_date->format('M d, Y') }}
                                        </p>
                                    </div>

                                    <div class="ml-4">
                                        <flux:button :href="route('my.claims.show', $claim)" size="sm" variant="outline"
                                            wire:navigate>
                                            {{ __('View Details') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-neutral-200 dark:border-neutral-700">
                    {{ $claims->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No claims found') }}</h3>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __('Get started by submitting your first health claim.') }}
                    </p>
                    <div class="mt-6">
                        <flux:button :href="route('my.claims.submit')" variant="primary" wire:navigate>
                            {{ __('Submit Claim') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>