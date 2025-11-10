<?php

use App\Models\ContributionPlan;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Contribution Plan Details'])] class extends Component
{
    public ContributionPlan $plan;

    public function mount(ContributionPlan $plan): void
    {
        $this->plan = $plan->load(['contributions']);
    }

    public function deletePlan(): void
    {
        // Check if plan has contributions
        if ($this->plan->contributions()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete contribution plan that has associated contributions.',
            ]);
            return;
        }

        $this->plan->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Contribution plan deleted successfully.',
        ]);

        $this->redirect(route('admin.contribution-plans.index'), navigate: true);
    }
}; ?>

<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white leading-tight">{{ __('Contribution Plan Details') }}</h2>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('admin.contribution-plans.edit', $plan)" variant="outline" wire:navigate>
                {{ __('Edit Plan') }}
            </flux:button>
            <flux:button :href="route('admin.contribution-plans.index')" variant="outline" wire:navigate>
                {{ __('Back to Plans') }}
            </flux:button>
        </div>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Plan Header -->
            <div class="flex items-center space-x-6 mb-8">
                <div class="flex-shrink-0">
                    <div class="h-20 w-20 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                        <span class="text-2xl font-medium text-green-800 dark:text-green-200">
                            {{ substr($plan->name, 0, 2) }}
                        </span>
                    </div>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h1>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ $plan->description ?? 'No description provided' }}</p>
                    <div class="flex items-center space-x-4 mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ ucfirst($plan->frequency) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button 
                        wire:click="deletePlan"
                        variant="danger"
                        wire:confirm="Are you sure you want to delete this contribution plan? This action cannot be undone."
                    >
                        {{ __('Delete Plan') }}
                    </flux:button>
                </div>
            </div>

            <!-- Plan Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Plan Information') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Plan Name') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $plan->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Description') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $plan->description ?? 'No description provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Amount') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">₦{{ number_format($plan->amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Frequency') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst($plan->frequency) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $plan->is_active ? 'Active' : 'Inactive' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Statistics -->
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Statistics') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Total Contributions') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $plan->contributions->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Total Amount Collected') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">₦{{ number_format($plan->contributions->sum('amount'), 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Paid Contributions') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $plan->contributions->where('status', 'paid')->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Pending Contributions') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $plan->contributions->where('status', 'pending')->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Created At') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $plan->created_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Recent Contributions -->
            <div class="mt-6">
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Recent Contributions') }}</h3>
                    @if($plan->contributions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                <thead class="bg-white dark:bg-neutral-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Member') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Amount') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Payment Date') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Status') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-neutral-800 divide-y divide-neutral-200 dark:divide-neutral-700">
                                    @foreach($plan->contributions->take(10) as $contribution)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $contribution->member->full_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                ₦{{ number_format($contribution->amount, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $contribution->payment_date->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $contribution->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                                    {{ ucfirst($contribution->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <flux:button 
                                                    :href="route('contributions.show', $contribution)" 
                                                    size="sm" 
                                                    variant="outline"
                                                    wire:navigate
                                                >
                                                    {{ __('View') }}
                                                </flux:button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($plan->contributions->count() > 10)
                            <div class="mt-4 text-center">
                                <flux:button :href="route('contributions.index', ['plan' => $plan->id])" variant="outline" size="sm" wire:navigate>
                                    {{ __('View All Contributions') }}
                                </flux:button>
                            </div>
                        @endif
                    @else
                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('No contributions found for this plan.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
