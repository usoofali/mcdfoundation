<?php

use App\Models\ContributionPlan;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deletePlan(ContributionPlan $plan): void
    {
        // Check if plan has contributions
        if ($plan->contributions()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete contribution plan that has associated contributions.',
            ]);
            return;
        }

        $plan->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Contribution plan deleted successfully.',
        ]);
    }

    public function with(): array
    {
        $query = ContributionPlan::query()
            ->withCount('contributions')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            });

        return [
            'plans' => $query->paginate(15),
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="leading-tight text-xl font-semibold text-gray-900 dark:text-white">{{ __('Contribution Plans') }}</h2>
        <flux:button icon="plus-circle" :href="route('admin.contribution-plans.create')" primary wire:navigate class="gap-2">
            
            {{ __('Create New Plan') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Search -->
            <div class="mb-6">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="{{ __('Search contribution plans...') }}" 
                    icon="magnifying-glass"
                />
            </div>

            <!-- Plans Table -->
            @if($plans->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Plan') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Amount') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Frequency') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Contributions') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                            @foreach($plans as $plan)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
                                                    <span class="text-sm font-medium text-green-700 dark:text-green-200">
                                                        {{ substr($plan->label, 0, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-neutral-900 dark:text-white">
                                                    {{ $plan->label }}
                                                </div>
                                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $plan->description ?? 'No description' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            ₦{{ number_format($plan->amount, 2) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ ucfirst($plan->frequency) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $plan->contributions_count }} {{ Str::plural('contribution', $plan->contributions_count) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:button 
                                                :href="route('admin.contribution-plans.show', $plan)" 
                                                size="sm" 
                                                variant="outline"
                                                wire:navigate
                                            >
                                                {{ __('View') }}
                                            </flux:button>
                                            
                                            <flux:button 
                                                :href="route('admin.contribution-plans.edit', $plan)" 
                                                size="sm" 
                                                variant="outline"
                                                wire:navigate
                                            >
                                                {{ __('Edit') }}
                                            </flux:button>
                                            
                                            <flux:button 
                                                wire:click="deletePlan({{ $plan->id }})"
                                                size="sm" 
                                                variant="danger"
                                                wire:confirm="Are you sure you want to delete this contribution plan? This action cannot be undone."
                                            >
                                                {{ __('Delete') }}
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $plans->links() }}
                </div>
            @else
                <x-empty-state 
                    title="{{ __('No Contribution Plans Found') }}" 
                    description="{{ __('No contribution plans match your current search criteria.') }}"
                />
            @endif
        </div>
    </div>
</div>
