<?php

use App\Models\Member;
use App\Models\State;
use App\Models\Lga;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Members'])] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $state_id = '';
    public string $lga_id = '';
    public string $registration_date_from = '';
    public string $registration_date_to = '';
    public int $perPage = 25;

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingStateId(): void
    {
        $this->resetPage();
        $this->lga_id = '';
    }

    public function updatingLgaId(): void
    {
        $this->resetPage();
    }

    public function updatingRegistrationDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingRegistrationDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->state_id = '';
        $this->lga_id = '';
        $this->registration_date_from = '';
        $this->registration_date_to = '';
        $this->resetPage();
    }

    public function getMembersProperty(): LengthAwarePaginator
    {
        return Member::query()
            ->with(['state', 'lga', 'contributionPlan', 'healthcareProvider'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('full_name', 'like', '%' . $this->search . '%')
                      ->orWhere('family_name', 'like', '%' . $this->search . '%')
                      ->orWhere('registration_no', 'like', '%' . $this->search . '%')
                      ->orWhere('nin', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, fn($query) => $query->where('status', $this->status))
            ->when($this->state_id, fn($query) => $query->where('state_id', $this->state_id))
            ->when($this->lga_id, fn($query) => $query->where('lga_id', $this->lga_id))
            ->when($this->registration_date_from, fn($query) => $query->where('registration_date', '>=', $this->registration_date_from))
            ->when($this->registration_date_to, fn($query) => $query->where('registration_date', '<=', $this->registration_date_to))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function getStatesProperty()
    {
        return State::orderBy('name')->get();
    }

    public function getLgasProperty()
    {
        if (!$this->state_id) {
            return collect();
        }
        
        return Lga::where('state_id', $this->state_id)->orderBy('name')->get();
    }

    public function getStatusOptionsProperty()
    {
        return [
            'pre_registered' => 'Pre-registered',
            'pending' => 'Pending Approval',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'terminated' => 'Terminated',
        ];
    }

    public function approveMember(Member $member): void
    {
        if (!auth()->user()->can('approve', $member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to approve members.',
            ]);
            return;
        }

        $member->approve();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member approved successfully.',
        ]);
    }

        public function rejectMember(Member $member): void
    {
        // Use update permission as proxy for reject authority
        if (!auth()->user()->can('update', $member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to reject members.',
            ]);
            return;
        }

        $member->update(['status' => 'inactive']);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member rejected successfully.',
        ]);
    }

    public function suspendMember(Member $member): void
    {
        if (!auth()->user()->can('update', $member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to suspend members.',
            ]);
            return;
        }

        $member->suspend();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member suspended successfully.',
        ]);
    }

    public function activateMember(Member $member): void
    {
        if (!auth()->user()->can('update', $member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to activate members.',
            ]);
            return;
        }

        $member->activate();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member activated successfully.',
        ]);
    }

    public function terminateMember(Member $member): void
    {
        if (!auth()->user()->can('delete', $member)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to terminate members.',
            ]);
            return;
        }

        $member->terminate();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Member terminated successfully.',
        ]);
    }
}; ?>

<div class="space-y-6">
            <!-- Page Header -->
            <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1.5">
                        <flux:heading size="xl" class="font-bold text-neutral-900 dark:text-white">
                            Members
                        </flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                            Manage member registrations and profiles
                        </flux:text>
                    </div>
                    <div>
                        <flux:button variant="primary" icon="plus" variant="primary" href="{{ route('members.create') }}" wire:navigate class="gap-2">
                            
                            Add Member
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Search -->
                    <div>
                        <flux:input 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Search members..." 
                            icon="magnifying-glass"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <flux:input wire:model.live="status" placeholder="Filter by status" />
                    </div>

                    <!-- State Filter -->
                    <div>
                        <flux:input wire:model.live="state_id" placeholder="Filter by state" />
                    </div>

                    <!-- LGA Filter -->
                    <div>
                        <flux:input wire:model.live="lga_id" placeholder="Filter by LGA" />
                    </div>
                </div>

                <!-- Date Range Filters -->
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <flux:input 
                            wire:model.live="registration_date_from" 
                            type="date" 
                            label="Registration Date From"
                        />
                    </div>
                    <div>
                        <flux:input 
                            wire:model.live="registration_date_to" 
                            type="date" 
                            label="Registration Date To"
                        />
                    </div>
                    <div class="flex items-end">
                        <flux:button variant="outline" wire:click="clearFilters" class="w-full justify-center">
                            Clear Filters
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Members Table -->
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    Member
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                                    Registration No
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                                    Location
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                                    Registration Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                            @forelse($this->members as $member)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                @if($member->photo_path)
                                                    <img class="h-10 w-10 rounded-full object-cover" 
                                                         src="{{ Storage::url($member->photo_path) }}" 
                                                         alt="{{ $member->full_name }}">
                                                @else
                                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-300 dark:bg-neutral-600">
                                                        <span class="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                                            {{ substr($member->full_name, 0, 1) }}{{ substr($member->family_name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <flux:text class="font-medium text-neutral-900 dark:text-white">
                                                    {{ $member->full_name }} {{ $member->family_name }}
                                                </flux:text>
                                                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $member->nin }}
                                                </flux:text>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $member->registration_no }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        <div>{{ $member->lga->name }}</div>
                                        <div class="text-xs">{{ $member->state->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pre_registered' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                'pending' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'inactive' => 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200',
                                                'suspended' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'terminated' => 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200',
                                            ];
                                        @endphp
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$member->status] ?? 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200' }}">
                                            {{ $this->statusOptions[$member->status] ?? $member->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $member->registration_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <flux:button variant="ghost" size="sm" href="{{ route('members.show', $member) }}" wire:navigate>
                                                View
                                            </flux:button>
                                            
                                            @if($member->status === 'pending')
                                                <flux:button variant="primary" size="sm" wire:click="approveMember({{ $member->id }})">
                                                    Approve
                                                </flux:button>
                                                <flux:button variant="danger" size="sm" wire:click="rejectMember({{ $member->id }})">
                                                    Reject
                                                </flux:button>
                                            @endif
                                            
                                            @if($member->status === 'active')
                                                <flux:button variant="danger" size="sm" wire:click="suspendMember({{ $member->id }})">
                                                    Suspend
                                                </flux:button>
                                            @endif
                                            
                                            @if($member->status === 'suspended')
                                                <flux:button variant="primary" size="sm" wire:click="activateMember({{ $member->id }})">
                                                    Activate
                                                </flux:button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <x-empty-state 
                                            title="No members found"
                                            description="No members match your current filters."
                                            icon="users"
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($this->members->hasPages())
                    <div class="px-6 py-3 border-t border-neutral-200 dark:border-neutral-700">
                        {{ $this->members->links() }}
                    </div>
                @endif
            </div>
        </div>
