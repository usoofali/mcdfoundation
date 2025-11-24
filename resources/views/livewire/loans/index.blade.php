<?php

use App\Models\Loan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public ?string $status = null;

    public ?int $memberId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Loan::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function loans()
    {
        return Loan::query()
            ->with(['member', 'approver'])
            ->when($this->search, function ($query) {
                $query->whereHas('member', function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('registration_no', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->memberId, fn ($query) => $query->where('member_id', $this->memberId))
            ->latest()
            ->paginate(15);
    }
}; ?>

<div>
    <div class="space-y-6">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1.5">
                    <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                        {{ __('Loans') }}
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Manage loan applications and repayments') }}
                    </flux:text>
                </div>
                @can('create', Loan::class)
                    <flux:button icon="plus" href="{{ route('loans.create') }}" variant="primary" class="w-full gap-2 sm:w-auto" wire:navigate>
                        
                        {{ __('New Loan') }}
                    </flux:button>
                @endcan
            </div>
        </div>


        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Search by member name or registration number...') }}" />

                    <flux:select wire:model.live="status" :label="__('Status')">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="disbursed">{{ __('Disbursed') }}</option>
                        <option value="repaid">{{ __('Repaid') }}</option>
                        <option value="defaulted">{{ __('Defaulted') }}</option>
                    </flux:select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th class="px-2 py-3 text-left text-xs font-semibold sm:px-4 sm:text-sm">{{ __('Date') }}</th>
                            <th class="px-2 py-3 text-left text-xs font-semibold sm:px-4 sm:text-sm">{{ __('Member') }}</th>
                            <th class="px-2 py-3 text-left text-xs font-semibold sm:px-4 sm:text-sm hidden sm:table-cell">{{ __('Amount') }}</th>
                            <th class="px-2 py-3 text-left text-xs font-semibold sm:px-4 sm:text-sm hidden sm:table-cell">{{ __('Approved') }}</th>
                            <th class="px-2 py-3 text-left text-xs font-semibold sm:px-4 sm:text-sm hidden sm:table-cell">{{ __('Balance') }}</th>
                            <th class="px-2 py-3 text-left text-xs font-semibold sm:px-4 sm:text-sm">{{ __('Status') }}</th>
                            <th class="px-2 py-3 text-left text-xs font-semibold sm:px-4 sm:text-sm">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->loans as $loan)
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <td class="px-2 py-3 text-xs sm:px-4 sm:text-sm">{{ $loan->created_at->format('M j, Y') }}</td>
                                <td class="px-2 py-3 sm:px-4">
                                    <div class="text-xs font-medium text-neutral-900 sm:text-sm dark:text-white">{{ $loan->member->full_name }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $loan->member->registration_no }}</div>
                                    <div class="mt-1 text-xs font-medium sm:hidden">
                                        {{ __('Amount:') }} {{ number_format($loan->amount, 2) }}
                                        @if($loan->approved_amount)
                                            | {{ __('Approved:') }} {{ number_format($loan->approved_amount, 2) }}
                                        @endif
                                        | {{ __('Balance:') }} {{ number_format($loan->outstanding_balance, 2) }}
                                    </div>
                                </td>
                                <td class="hidden px-2 py-3 text-xs sm:table-cell sm:px-4 sm:text-sm">{{ number_format($loan->amount, 2) }}</td>
                                <td class="hidden px-2 py-3 text-xs sm:table-cell sm:px-4 sm:text-sm">{{ $loan->approved_amount ? number_format($loan->approved_amount, 2) : __('N/A') }}</td>
                                <td class="hidden px-2 py-3 text-xs sm:table-cell sm:px-4 sm:text-sm">{{ number_format($loan->outstanding_balance, 2) }}</td>
                                <td class="px-2 py-3 sm:px-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $loan->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                        {{ $loan->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                        {{ $loan->status === 'disbursed' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                        {{ $loan->status === 'repaid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                        {{ $loan->status === 'defaulted' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $loan->status)) }}
                                    </span>
                                </td>
                                <td class="px-2 py-3 sm:px-4">
                                    <div class="flex items-center gap-2">
                                        <flux:button href="{{ route('loans.show', $loan) }}" variant="ghost" size="sm" class="w-full sm:w-auto" wire:navigate>
                                            {{ __('View') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-neutral-500">
                                    {{ __('No loans found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                {{ $this->loans->links() }}
            </div>
        </div>
    </div>

    @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 z-50">
        <x-alert variant="error" :timeout="5000">
            {{ session('error') }}
        </x-alert>
    </div>
    @endif

    @if (session()->has('success'))
        <div class="fixed bottom-4 right-4 z-50">
        <x-alert variant="success" :timeout="5000">
            {{ session('success') }}
        </x-alert>
    </div>
    @endif
</div>
